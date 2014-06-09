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

    public function setupHashFile(\SplFileInfo $dir, $hashFilename, $perms)
    {
        if (!$dir->isDir()) {
            throw new JSONDiskCacheException("{$dir} is not a dir");
        }

        $fileName = $dir->getRealPath() . DIRECTORY_SEPARATOR . $hashFilename;
        $hashFile = new \SplFileInfo($fileName);
        if (!file_exists($hashFile)) {
            if (!touch($hashFile)) {
                throw new JSONDiskCacheException("Error creating empty hash file {$hashFile->getFilename()}");
            }
        }

        if (!$hashFile->isFile()) {
            throw new JSONDiskCacheException("{$hashFile->getFilename()} is not a file");
        }

        $perms = $hashFile->getPerms() & 0777;
        if ($perms !== $perms && !chmod($hashFile, $perms)) {
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