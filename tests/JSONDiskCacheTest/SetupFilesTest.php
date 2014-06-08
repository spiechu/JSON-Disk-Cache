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

    /**
     * Dir permissions.
     *
     * @var int
     */
    protected $perms = 0700;

    public function testDirIsCreated()
    {
        $dirToCreate = __DIR__ . '/test_dir';

        @unlink($dirToCreate);
        $this->assertFalse(file_exists($dirToCreate), 'Should not be directory there');

        $this->setupFiles->setupCacheDir($dirToCreate, $this->perms);
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