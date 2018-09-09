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
use Grav\Common\Utils;
use Grav\Common\Grav;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Backups
{
    const BACKUP_FILENAME_REGEXZ = "#(.*)--(\d*).zip#";
    protected $backup_dir;
    protected $backup_dateformat = 'YmdHis';

    protected $backups = [];

    public function init()
    {
        if (is_null($this->backup_dir)) {
            $this->backup_dir = Grav::instance()['locator']->findResource('backup://', true, true);
            Folder::create($this->backup_dir);
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

    public function getBackupConfigurations()
    {
        return Grav::instance()['config']->get('backups.backups');
    }

    public function getBackupNames()
    {
        return array_column($this->getBackupConfigurations(), 'name');
    }

    public function getAvailableBackups()
    {
        $backups_itr = new \GlobIterator($this->backup_dir . '/*.zip', \FilesystemIterator::KEY_AS_FILENAME);
        $inflector = Grav::instance()['inflector'];
        $long_date_format = DATE_RFC850;

        foreach ($backups_itr as $name => $file) {

            if (preg_match($this::BACKUP_FILENAME_REGEXZ, $name, $matches)) {
                $date = \DateTime::createFromFormat($this->backup_dateformat, $matches[2]);
                $timestamp = $date->getTimestamp();
                $backup = new \stdClass();
                $backup->title = $inflector->titleize($matches[1]);
                $backup->date = $date->format($long_date_format);
                $backup->filename = $name;
                $backup->path = $file->getPathname();
                $this->backups[$timestamp] = $backup;
            }

        }
        // Reverse Key Sort to get in reverse date order
        krsort($this->backups);

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
    public function backup($id = 0, callable $status = null)
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
        $date = date($this->backup_dateformat, time());
        $filename = trim($name, '_') . '--' . $date . '.zip';
        $destination = $this->backup_dir. DS . $filename;
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
            'exclude_files' => $this->convertExclude($backup->exclude_files),
            'exclude_paths' => $this->convertExclude($backup->exclude_paths),
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

    protected function convertExclude($exclude)
    {
        $lines = preg_split("/[\s,]+/", $exclude);
        return array_map('trim', $lines, array_fill(0,count($lines),'/'));
    }

}
