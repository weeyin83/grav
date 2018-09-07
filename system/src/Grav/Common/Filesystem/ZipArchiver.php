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

    public function extract($destination)
    {
        $zip = new \ZipArchive();
        $archive = $zip->open($this->archive_file);

        if ($archive === true) {
            Folder::mkdir($destination);

            if (!$zip->extractTo($destination)) {
                throw new \RuntimeException('ZipArchiver: ZIP failed to extract ' . $this->archive_file . ' to ' . $destination);
            }

            $zip->close();
            return $this;
        }

        throw new \RuntimeException('ZipArchiver: Failed to open ' . $this->archive_file);
    }

    public function compress($source)
    {
        if (!extension_loaded('zip')) {
            throw new \InvalidArgumentException('ZipArchiver: Zip PHP module not installed...');
        }

        if (!file_exists($source)) {
            throw new \InvalidArgumentException('ZipArchiver: ' . $source . ' cannot be found...');
        }

        $zip = new \ZipArchive();
        if (!$zip->open($this->archive_file, \ZipArchive::CREATE)) {
            throw new \InvalidArgumentException('ZipArchiver:' . $this->archive_file . ' cannot be created...');
        }

        // Get real path for our folder
        $rootPath = realpath($source);

        foreach ($this->getArchiveFiles($rootPath) as $name => $file) {
            $filePath = $file->getPathname();
            $relativePath = ltrim(substr($filePath, strlen($rootPath)), '/');

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return $this;
    }

    public function addEmptyFolders($folders)
    {
        if (!extension_loaded('zip')) {
            throw new \InvalidArgumentException('ZipArchiver: Zip PHP module not installed...');
        }

        $zip = new \ZipArchive();
        if (!$zip->open($this->archive_file)) {
            throw new \InvalidArgumentException('ZipArchiver: ' . $this->archive_file . ' cannot be opened...');
        }

        foreach($folders as $folder) {
            $zip->addEmptyDir($folder);
        }

        $zip->close();

        return $this;
    }
}
