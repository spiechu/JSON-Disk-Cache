<?php

/*
 * This file is part of the JSONDiskCache package.
 *
 * (c) Dawid Spiechowicz <spiechu@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiechu\JSONDiskCacheTest;

use Spiechu\JSONDiskCache\JSONDiskCacheException;
use Spiechu\JSONDiskCache\SetupFiles;

/**
 * @author Dawid Spiechowicz <spiechu@gmail.com>
 * @since 0.1.0
 */
class SetupFilesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tested object.
     *
     * @var SetupFiles
     */
    private $setupFiles;

    private $testDir;

    public function testDirIsCreated()
    {
        $this->prepareTestDir();
        $this->assertFalse(file_exists($this->testDir), 'Should be nothing at ' . realpath($this->testDir));

        $perms = 0777;
        $this->setupFiles->setupCacheDir($this->testDir, $perms);
        $this->assertTrue(file_exists($this->testDir), 'Directory should be created');
    }

    protected function prepareTestDir()
    {
        try {
            // now I know why I hate Windows so much...
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists($this->testDir)) {
                // dirty workaround readonly attribute and permission denied
                exec("RMDIR {$this->testDir} /S /Q", $output, $returnVar);
            } else {
                unlink($this->testDir);
            }
        } catch (\Exception $e) {
            $exMsg = $e->getMessage();
            switch (true) {
                case preg_match('/no such file or directory/i', $exMsg):
                    // we're ok, proceed
                    break;
                case preg_match('/permission denied/i', $exMsg):
                    $this->fail('Cant delete, permission denied to directory');
                    break;
                default:
                    $this->fail($exMsg);
                    break;
            }
        }
    }

    /**
     * @depends testDirIsCreated
     * @expectedException \Spiechu\JSONDiskCache\JSONDiskCacheException
     * @expectedExceptionMessage is not a dir
     */
    public function testFakeDir()
    {
        $filePretendingDir = $this->testDir . DIRECTORY_SEPARATOR . 'fake_test_dir';
        touch($filePretendingDir);

        $perms = 0777;
        $this->setupFiles->setupCacheDir($filePretendingDir, $perms);
    }

    /**
     * @depends testDirIsCreated
     * @expectedException \Spiechu\JSONDiskCache\JSONDiskCacheException
     * @expectedExceptionMessage is not readable or writable
     */
    public function testDirWritePerms()
    {
        $perms = 0555;
        $errDir = $this->testDir . DIRECTORY_SEPARATOR . 'write_perm_err';
        if (mkdir($errDir, $perms)) {
            $this->setupFiles->setupCacheDir($errDir, $perms);
        }
    }

    /**
     * @depends testDirIsCreated
     * @expectedException \Spiechu\JSONDiskCache\JSONDiskCacheException
     * @expectedExceptionMessage is not readable or writable
     */
    public function testDirReadPerms()
    {
        $perms = 0333;
        $errDir = $this->testDir . DIRECTORY_SEPARATOR . 'read_perm_err';
        if (mkdir($errDir, $perms)) {
            $this->setupFiles->setupCacheDir($errDir, $perms);
        }
    }

    /**
     * @expectedException \Spiechu\JSONDiskCache\JSONDiskCacheException
     */
    public function testIllegalDirLocation()
    {
        $perms = 0777;
        $errDir = realpath($this->testDir . '/../../../..') . DIRECTORY_SEPARATOR . 'illegal_location_dir';
        $this->setupFiles->setupCacheDir($errDir, $perms);
    }

    protected function setUp()
    {
        $this->setupFiles = new SetupFiles();
        $this->testDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'test_dir';
    }

    protected function tearDown()
    {
        unset($this->setupFiles);
    }


}