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
    public function __construct($type = 'zip')
    {
        if ($type == 'zip') {
            return new ZipArchiver;
        }
    }
}
