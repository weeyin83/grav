<?php
/**
 * @package    Grav.Common.FileSystem
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Filesystem;

class Archiver
{
    protected $options = [
        'ignore_files' => ['.DS_Store'],
        'ignore_paths' => []
    ];

    protected $destination;

    public static function create($compression)
    {
        if ($compression == 'zip') {
            return new ZipArchiver();
        } else {
            return new ZipArchiver();
        }
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options + $this->options;

        return $this;
    }

    public function addFolder($folder)
    {
        return $this;
    }

}
