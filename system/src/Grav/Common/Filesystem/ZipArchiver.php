<?php
/**
 * @package    Grav.Common.FileSystem
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Filesystem;

class ZipArchiver extends GenericArchiver
{

    public function addFolder($source)
    {
        $base = '';

        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($this->destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if($this->options['include_folder']) {
            $base = basename($source) . '/';
            //$zip->addEmptyDir(basename($source) . '/');
        }

        if (is_dir($source) === true)
        {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));
                $local_file = str_replace($source . '/', '', $base.$file);

                if (is_dir($file) === true) {
                    $zip->addEmptyDir($local_file . '/');
//                    $zip->addEmptyDir(str_replace($source . '/', '', $base.$file . '/'));
                }
                else if (is_file($file) === true) {
                    $zip->addFile($file, $local_file);
//                    $zip->addFromString(str_replace($source . '/', '', $base.$file, file_get_contents($file));

                }
            }
        } else if (is_file($source) === true) {
            $zip->addFile($base.basename($source), $source);
//            $zip->addFromString($base.basename($source), file_get_contents($source));
        }

        $zip->close();

        return $this;
    }
}
