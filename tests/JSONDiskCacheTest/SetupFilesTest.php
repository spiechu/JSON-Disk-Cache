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
class JSONDiskCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tested object.
     *
     * @var SetupFiles
     */
    protected $setupFiles;

    protected function setUp()
    {
        $this->setupFiles = new SetupFiles();
    }

    protected function tearDown()
    {
        unset($this->setupFiles);
    }


}