<?php
/**
 * @package    Grav.Common.Backup
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Backup;

use Grav\Common\Filesystem\Archiver;
use Grav\Common\Grav;
use Grav\Common\Inflector;

class Backup
{
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

    /**
     * Backup
     *
     * @param string|null   $destination
     * @param callable|null $messager
     *
     * @return null|string
     */
    public static function backup($destination = null, callable $messager = null)
    {
        if (!$destination) {
            $destination = Grav::instance()['locator']->findResource('backup://', true);

            if (!$destination) {
                throw new \RuntimeException('The backup folder is missing.');
            }
        }

        $name = substr(strip_tags(Grav::instance()['config']->get('site.title', basename(GRAV_ROOT))), 0, 20);

        $inflector = new Inflector();

        if (is_dir($destination)) {
            $date = date('YmdHis', time());
            $filename = trim($inflector->hyphenize($name), '-') . '-' . $date . '.zip';
            $destination = rtrim($destination, DS) . DS . $filename;
        }

        $messager && $messager([
            'type' => 'message',
            'level' => 'info',
            'message' => 'Creating new Backup "' . $destination . '"'
        ]);
        $messager && $messager([
            'type' => 'message',
            'level' => 'info',
            'message' => ''
        ]);

//        $zip = new \ZipArchive();
//        $zip->open($destination, \ZipArchive::CREATE);

        $max_execution_time = ini_set('max_execution_time', 600);

//        static::folderToZip(GRAV_ROOT, $zip, strlen(rtrim(GRAV_ROOT, DS) . DS), $messager);

        $options = [
            'ignore_files' => static::$ignore_files,
            'ignore_paths' => static::$ignore_paths,
        ];

        /** @var Archiver $archiver */
        $archiver = Archiver::create('zip');
        $archiver->setDestination($destination)->setOptions($options)->addFolder(GRAV_ROOT);

        $messager && $messager([
            'type' => 'progress',
            'percentage' => false,
            'complete' => true
        ]);

        $messager && $messager([
            'type' => 'message',
            'level' => 'info',
            'message' => ''
        ]);
        $messager && $messager([
            'type' => 'message',
            'level' => 'info',
            'message' => 'Saving and compressing archive...'
        ]);

        if ($max_execution_time !== false) {
            ini_set('max_execution_time', $max_execution_time);
        }

        return $destination;
    }



}
