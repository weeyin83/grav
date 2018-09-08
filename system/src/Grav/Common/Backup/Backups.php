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
use Grav\Common\Grav;
use Grav\Common\Inflector;

class Backups
{
    protected $backup_dir;

    protected static $ignore_paths = [
        'backup',
        'cache',
        'images',
        'logs',
        'tmp',
    ];

    protected static $ignore_files = [
        '.DS_Store',
        '.git',
        '.svn',
        '.hg',
        '.idea',
        '.vscode',
        'node_modules',
    ];

    public function init()
    {
        $this->backup_dir = Grav::instance()['locator']->findResource('backup://', true);

        if (!$this->backup_dir) {
            Folder::mkdir($this->backup_dir);
        }
    }

    /**
     * Backup
     *
     * @param string|null   $destination
     * @param callable|null $status
     *
     * @return null|string
     */
    public function backup($destination = null, callable $status = null)
    {
        if (!$destination) {
            $destination = $this->backup_dir;
        }

        $name = substr(strip_tags(Grav::instance()['config']->get('site.title', basename(GRAV_ROOT))), 0, 20);

        $inflector = new Inflector();

        if (is_dir($destination)) {
            $date = date('YmdHis', time());
            $filename = trim($inflector->hyphenize($name), '-') . '-' . $date . '.zip';
            $destination = rtrim($destination, DS) . DS . $filename;
        }

        $max_execution_time = ini_set('max_execution_time', 600);

        $options = [
            'ignore_files' => static::$ignore_files,
            'ignore_paths' => static::$ignore_paths,
        ];

        /** @var Archiver $archiver */
        $archiver = Archiver::create('zip');
        $archiver->setArchive($destination)->setOptions($options)->compress(GRAV_ROOT, $status)->addEmptyFolders($options['ignore_paths'], $status);

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

}
