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

class SmartyTimeSpanModifierTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var JsonDiskCache
     */
    protected $_jsonDiskCache;
    protected $_cacheDirPath;

    protected function setUp()
    {
        $this->_cacheDirPath = __DIR__ . '/../../testcache';
        $this->_jsonDiskCache = new JsonDiskCache($this->_cacheDirPath, 'testdomain');
    }

    protected function tearDown()
    {
        foreach (scandir($this->_cacheDirPath) as $file) {
            if ($file !== '.' &&
            $file !== '..' &&

            // PHP 5.4 hax (new Object)->method() call in one line
            (new \SplFileInfo($this->_cacheDirPath . '/' . $file))->getExtension() === 'cache') {
                unlink($this->_cacheDirPath . '/' . $file);
            }
        }
        rmdir($this->_cacheDirPath);
    }

    public function testCacheFilesCreation()
    {
        if (!file_exists($this->_cacheDirPath . '/hashtable.cache')) {
            $this->fail('File hashtable.cache doesnt exist');
        }
        if (!file_exists($this->_cacheDirPath . '/testdomain.cache')) {
            $this->fail('File testdomain.cache doesnt exist');
        }
    }

}
