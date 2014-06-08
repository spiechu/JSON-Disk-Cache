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
    protected $setupFiles;

    public function testDirIsCreated()
    {
        $dirToCreate = __DIR__ . '/../../test_dir';
        $perms = 0777;

        try {
            rmdir($dirToCreate);
        } catch (\Exception $e) {
            $exMsg = $e->getMessage();
            if (preg_match('/permission denied/i', $exMsg)) {
                $this->fail('Cant delete, permission denied to directory');
            } else {
                $this->fail($exMsg);
            }
        }
        $this->assertFalse(file_exists($dirToCreate), 'Should be nothing at ' . realpath($dirToCreate));

        $this->setupFiles->setupCacheDir($dirToCreate, $perms);
        $this->assertTrue(file_exists($dirToCreate), 'Directory should be created');
    }


    protected function setUp()
    {
        $this->setupFiles = new SetupFiles();
    }

    protected function tearDown()
    {
        unset($this->setupFiles);
    }


}