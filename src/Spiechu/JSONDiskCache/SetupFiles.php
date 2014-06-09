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
        if (!($dir->isReadable() && $dir->isWritable())) {
            throw new JSONDiskCacheException("{$dirToCreate} is not readable or writable");
        }

        return $dir;
    }

    public function setupFile(\SplFileInfo $dir, $filename, $perms)
    {
        if (!$dir->isDir()) {
            throw new JSONDiskCacheException("{$dir} is not a dir");
        }

        $hashFile = new \SplFileInfo($dir->getRealPath() . DIRECTORY_SEPARATOR . $filename);
        if (!file_exists($hashFile) && !touch($hashFile)) {
            throw new JSONDiskCacheException("Error creating empty hash file {$hashFile->getFilename()}");
        }

        if (!$hashFile->isFile()) {
            throw new JSONDiskCacheException("{$hashFile->getFilename()} is not a file");
        }

        $filePerms = $hashFile->getPerms() & 0777;
        if ($filePerms !== $perms && !chmod($hashFile, $perms)) {
            throw new JSONDiskCacheException(
                "Cant change file permissions at {$hashFile->getFilename()}"
            );
        }

        if (!($hashFile->isReadable() && $hashFile->isWritable())) {
            throw new JSONDiskCacheException("{$hashFile->getFilename()} is not readable or writable");
        }

        return $hashFile;
    }
}