<?php

/*
 * This file is part of the TimeSpan package.
 *
 * (c) Dawid Spiechowicz <spiechu@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiechu\Tests\JsonDiskCache;

use Spiechu\JsonDiskCache\JsonDiskCache;

class SmartyTimeSpanModifierTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var JsonDiskCache
     */
    protected $_jsonDiskCache;

    protected function setUp()
    {
        $cacheDirPath = __DIR__ .
            DIRECTORY_SEPARATOR .
            '..' .
            DIRECTORY_SEPARATOR .
            'testcache';
        $this->_jsonDiskCache = new JsonDiskCache($cacheDirPath);
    }
}
