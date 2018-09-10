<?php
/**
 * @package    Grav.Common.Backup
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Backup;

use Grav\Common\Filesystem\Archiver;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Inflector;
use Grav\Common\Scheduler\Job;
use Grav\Common\Scheduler\Scheduler;
use Grav\Common\Utils;
use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventDispatcher;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Backups
{
    const BACKUP_FILENAME_REGEXZ = "#(.*)--(\d*).zip#";
    const BACKUP_DATE_FORMAT = 'YmdHis';
    protected static $backup_dir;

    protected $backups = null;

    public function init()
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = Grav::instance()['events'];
        $dispatcher->addListener('onSchedulerInitialized', [$this, 'onSchedulerInitialized']);

        Grav::instance()->fireEvent('onBackupsInitialized', new Event(['backups' => $this]));
    }

    public function setup()
    {
        if (is_null(static::$backup_dir)) {
            static::$backup_dir = Grav::instance()['locator']->findResource('backup://', true, true);
            Folder::create(static::$backup_dir);
        }
    }



    public function onSchedulerInitialized(Event $event)
    {
        /** @var Scheduler $scheduler */
        $scheduler = $event['scheduler'];

        /** @var Inflector $inflector */
        $inflector = Grav::instance()['inflector'];

        foreach ($this->getBackupProfiles() as $id => $profile) {
            $at = $profile['schedule_at'];
            $name = $inflector->hyphenize($profile['name']);
            $logs = 'logs/backup-' . $name . '.out';
            /** @var Job $job */
            $job = $scheduler->addFunction('Grav\Common\Backup\Backups::backup', [$id], $name );
            $job->at($at);
            $job->output($logs);
        }
    }

    public function getBackupDownloadUrl($backup, $base_url)
    {
        $param_sep = $param_sep = Grav::instance()['config']->get('system.param_sep', ':');

        $download = urlencode(base64_encode($backup));
        $url      = rtrim(Grav::instance()['uri']->rootUrl(true), '/') . '/' . trim($base_url,
                '/') . '/task' . $param_sep . 'backup/download' . $param_sep . $download . '/admin-nonce' . $param_sep . Utils::getNonce('admin-form');
        return $url;
    }

    public function getBackupProfiles()
    {
        return Grav::instance()['config']->get('backups.backups');
    }

    public function getBackupNames()
    {
        return array_column($this->getBackupProfiles(), 'name');
    }

    public function getTotalBackupsSize()
    {
        $backups = $this->getAvailableBackups();
        $size = array_sum(array_column($backups, 'size'));

        return $size ?? 0;
    }

    public function getAvailableBackups()
    {
        if (is_null($this->backups)) {
            $this->backups = [];
            $backups_itr = new \GlobIterator(static::$backup_dir . '/*.zip', \FilesystemIterator::KEY_AS_FILENAME);
            $inflector = Grav::instance()['inflector'];
            $long_date_format = DATE_RFC850;

            /**
             * @var string $name
             * @var \SplFileInfo $file
             */
            foreach ($backups_itr as $name => $file) {

                if (preg_match($this::BACKUP_FILENAME_REGEXZ, $name, $matches)) {
                    $date = \DateTime::createFromFormat($this::BACKUP_DATE_FORMAT, $matches[2]);
                    $timestamp = $date->getTimestamp();
                    $backup = new \stdClass();
                    $backup->title = $inflector->titleize($matches[1]);
                    $backup->time = $date;
                    $backup->date = $date->format($long_date_format);
                    $backup->filename = $name;
                    $backup->path = $file->getPathname();
                    $backup->size = $file->getSize();
                    $this->backups[$timestamp] = $backup;
                }

            }
            // Reverse Key Sort to get in reverse date order
            krsort($this->backups);
        }

        return $this->backups;
    }

    /**
     * Backup
     *
     * @param int   $id
     * @param callable|null $status
     *
     * @return null|string
     */
    public static function backup($id = 0, callable $status = null)
    {
        $config = Grav::instance()['config']->get('backups');
        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];

        if (isset($config['backups'][$id])) {
            $backup = (object) $config['backups'][$id];
        } else {
            throw new \RuntimeException('No backups defined...');
        }

        $name = Grav::instance()['inflector']->underscorize($backup->name);
        $date = date(static::BACKUP_DATE_FORMAT, time());
        $filename = trim($name, '_') . '--' . $date . '.zip';
        $destination = static::$backup_dir . DS . $filename;
        $max_execution_time = ini_set('max_execution_time', 600);
        $backup_root = $backup->root;

        if ($locator->isStream($backup_root)) {
            $backup_root = $locator->findResource($backup_root);
        } else {
            $backup_root = rtrim(GRAV_ROOT . $backup_root, '/');
        }

        if (!file_exists($backup_root)) {
            throw new \RuntimeException("Backup location: " . $backup_root . ' does not exist...');
        }

        $options = [
            'exclude_files' => static::convertExclude($backup->exclude_files),
            'exclude_paths' => static::convertExclude($backup->exclude_paths),
        ];

        /** @var Archiver $archiver */
        $archiver = Archiver::create('zip');
        $archiver->setArchive($destination)->setOptions($options)->compress($backup_root, $status)->addEmptyFolders($options['exclude_paths'], $status);

        $status && $status([
            'type' => 'message',
            'message' => 'Done...',
        ]);

        $status && $status([
            'type' => 'progress',
            'complete' => true
        ]);

        if ($max_execution_time !== false) {
            ini_set('max_execution_time', $max_execution_time);
        }

        // Log the backup
        Grav::instance()['log']->error('Backup Created: ' . $destination);

        return $destination;
    }

    protected static function convertExclude($exclude)
    {
        $lines = preg_split("/[\s,]+/", $exclude);
        return array_map('trim', $lines, array_fill(0,count($lines),'/'));
    }

}
