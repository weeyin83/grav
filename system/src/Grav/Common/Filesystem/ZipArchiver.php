<?php
/**
 * @package    Grav.Common.FileSystem
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Filesystem;

class ZipArchiver extends Archiver
{

    public function addFolder($source)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($this->destination, \ZipArchive::CREATE)) {
            return false;
        }

        // Get real path for our folder
        $rootPath = realpath($source);

        $ignore_folders = $this->options['ignore_paths'];
        $ignore_files = $this->options['ignore_files'];

//        $files = new \RecursiveIteratorIterator(
//            new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS)
//        );

        $dirItr    = new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::UNIX_PATHS);
        $filterItr = new RecursiveDirectoryFilterIterator($dirItr, $rootPath, $ignore_folders, $ignore_files);
        $files       = new \RecursiveIteratorIterator($filterItr, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $name => $file)
        {
            $filePath = $file->getPathname();
            $relativePath = ltrim(substr($filePath, strlen($rootPath)), '/');

            if (!$file->isDir())
            {
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            } else {
                $zip->addEmptyDir($relativePath);
            }
        }

        // Add back ignored folders
        foreach($ignore_folders as $folder) {
            $zip->addEmptyDir($folder);
        }

        // Zip archive will be created only after closing object
        $zip->close();

        return $this;
    }
}
