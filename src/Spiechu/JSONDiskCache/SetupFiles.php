<?php

/*
 * This file is part of the JSONDiskCache package.
 *
 * (c) Dawid Spiechowicz <spiechu@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiechu\JSONDiskCache;

class SetupFiles
{
    public function setupCacheDir($dirToCreate, $perms)
    {
        $dir = new \SplFileInfo($dirToCreate);
        if (!file_exists($dir)) {
            $result = @mkdir($dir, $perms, true);
            if (!$result) {
                throw new JSONDiskCacheException(error_get_last()['message']);
            }
        }
        if (!$dir->isDir()) {
            throw new JSONDiskCacheException("{$dirToCreate} is not a dir");
        }
        if (!($dir->isReadable() || $dir->isWritable())) {
            throw new JSONDiskCacheException("{$dirToCreate} is not readable or writable");
        }

        return $dir;
    }
}