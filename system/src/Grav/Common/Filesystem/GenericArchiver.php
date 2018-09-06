<?php
/**
 * @package    Grav.Common.FileSystem
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Filesystem;

class GenericArchiver
{
    protected $options = [
        'ignore' => ['.DS_Store'],
        'include_folder' => false
    ];

    protected $destination;

    public function setDestination($destination)
    {
        $this->destination = $destination;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $this->options + $options;

        return $this;
    }

    public function addFolder($folder)
    {
        return $this;
    }

}
