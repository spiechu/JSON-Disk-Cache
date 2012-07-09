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

use Spiechu\JSONDiskCache\JSONDiskCache;

/**
 * @author Dawid Spiechowicz <spiechu@gmail.com>
 * @since 0.1.0
 */
class JSONDiskCacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    const DOMAIN = 'testdomain';

    /**
     * Relative path to cache dir.
     *
     * @var string
     */
    const TEST_CACHE_DIR = '/../../testcache';

    /**
     * Main tested object.
     *
     * @var JSONDiskCache
     */
    protected $_jsonDiskCache;

    /**
     * Full path to cache dir.
     *
     * @var string
     */
    protected $_cacheDirPath;

    /**
     * Integer values to test.
     *
     * Uses PHP 5.4 array shorthand creation
     *
     * @var array
     */
    protected static $_integerTestValues = [[-12345], [-5], [0], [5], [12345]];

    /**
     * String values to test.
     *
     * @var array
     */
    protected static $_stringTestValues =  [[''], ['hello'], ['0'], ['This is short text'], ['Stolen from www.lipsum.com: At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat']];

    protected function setUp()
    {
        $this->_cacheDirPath = __DIR__ . self::TEST_CACHE_DIR;
        $this->_jsonDiskCache = new JSONDiskCache($this->_cacheDirPath, self::DOMAIN);
    }

    protected function tearDown()
    {
        // make sure JSONDiskCache writes all to files
        unset($this->_jsonDiskCache);

        foreach (scandir($this->_cacheDirPath) as $file) {
            if ($file !== '.' && $file !== '..' &&
            // PHP 5.4 (new Object)->method() call in one line
            (new \SplFileInfo($this->_cacheDirPath . '/' . $file))->getExtension() === 'cache') {
                unlink($this->_cacheDirPath . '/' . $file);
            }
        }

        if (!rmdir($this->_cacheDirPath)) {
            $this->fail('Test dir should be empty');
        }
    }

    /**
     * Forces cache object to write cache data to files.
     */
    protected function recreateJsonObject()
    {
        unset($this->_jsonDiskCache);
        $this->_jsonDiskCache = new JSONDiskCache($this->_cacheDirPath, self::DOMAIN);
    }

    public function testCurrentDomain()
    {
        $this->assertSame($this->_jsonDiskCache->getDomain(), self::DOMAIN);
    }

    public function testCacheFilesCreation()
    {
        $this->assertFileExists(
                $this->_cacheDirPath . '/hashtable.cache', 'File hashtable.cache doesnt exist');

        $this->assertFileExists(
                $this->_cacheDirPath . '/testdomain.cache', 'File testdomain.cache doesnt exist');

        $this->assertFileNotExists(
                $this->_cacheDirPath . '/global.cache', 'File global.cache shouldnt exist');
    }

    /**
     * @dataProvider integerValuesProvider
     */
    public function testIntegerValue($integer)
    {
        $this->_jsonDiskCache->set('integer', $integer);

        $this->assertSame($this->_jsonDiskCache->get('integer'), $integer);
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->get('integer'), $integer);
    }

    public function integerValuesProvider()
    {
        return self::$_integerTestValues;
    }

    /**
     * @dataProvider stringValuesProvider
     */
    public function testStringValue($string)
    {
        $this->_jsonDiskCache->set('string', $string);

        $this->assertSame($this->_jsonDiskCache->get('string'), $string);
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->get('string'), $string);
    }

    public function stringValuesProvider()
    {
        return self::$_stringTestValues;
    }

    /**
     * @dataProvider arrayValuesProvider
     */
    public function testArrayValue($array)
    {
        $this->_jsonDiskCache->set('array', $array);

        $this->assertSame($this->_jsonDiskCache->get('array'), $array);
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->get('array'), $array);
    }

    public function arrayValuesProvider()
    {
        $valuesArray = [];
        foreach (self::$_integerTestValues as $key => $val) {
            $valuesArray[] = [$val, self::$_stringTestValues[$key]];
        }

        return $valuesArray;
    }

    public function testObjectCache()
    {
        $object = new \stdClass();
        $object->val1 = 1;
        $object->val2 = 'some string';
        $object->val3 = [0,1,2,'0','1','2'];
        $object->val4 = self::$_integerTestValues;
        $object->val5 = self::$_stringTestValues;

        $this->_jsonDiskCache->set('object', $object);

        $this->assertSame($this->_jsonDiskCache->get('object'), $object);
        $this->recreateJsonObject();
        $this->assertEquals($this->_jsonDiskCache->get('object'), $object);
    }

    public function testGetSetFunctionFetchWithoutParams()
    {
        $this->_jsonDiskCache->getSet('function without params', [$this, 'fetchWithoutParams']);

        $this->assertSame($this->_jsonDiskCache->get('function without params'), $this->fetchWithoutParams());
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->get('function without params'), $this->fetchWithoutParams());
    }

    /**
     * Method can be executed only 3 times.
     */
    public function fetchWithoutParams()
    {
        static $execution = 0;

        $this->assertNotEquals($execution, 3);
        $execution++;

        return 'This is my fetched value';
    }

    public function testGetSetFunctionFetchWithOneParam()
    {
        $this->_jsonDiskCache->getSet(['function with one param', 159], [$this, 'fetchWithOneParam', 159]);

        $this->assertSame($this->_jsonDiskCache->get(['function with one param', 159]), $this->fetchWithOneParam(159));
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->get(['function with one param', 159]), $this->fetchWithOneParam(159));
    }

    /**
     * Method can be executed only 3 times.
     */
    public function fetchWithOneParam($param)
    {
        $this->assertSame($param, 159);

        static $execution = 0;

        $this->assertNotEquals($execution, 3);
        $execution++;

        return 'This is my fetched value with one param';
    }

    public function testClearCache()
    {
        $this->_jsonDiskCache->set('clearme', 'value');

        $this->assertTrue($this->_jsonDiskCache->clear('clearme'));
        $this->assertNull($this->_jsonDiskCache->get('clearme'));

        $this->_jsonDiskCache->set('clearme', 'value');

        $this->recreateJsonObject();
        $this->assertTrue($this->_jsonDiskCache->clear('clearme'));
        $this->assertNull($this->_jsonDiskCache->get('clearme'));
    }

    /**
     * @depends testIntegerValue
     */
    public function testGlobalValidCacheTime()
    {
        $this->_jsonDiskCache->setValidTime(1);
        $this->_jsonDiskCache->set('timed off data', 12345);

        sleep(2);
        $this->assertNull($this->_jsonDiskCache->get('timed off data'));
    }

    /**
     * @depends testIntegerValue
     */
    public function testFunctionValidCacheTime()
    {
        $this->_jsonDiskCache->set('timed off data', 12345, 1);
        $this->_jsonDiskCache->set('not timed off data', 6789, 2);

        sleep(2);
        $this->assertNull($this->_jsonDiskCache->get('timed off data'));
        $this->assertSame($this->_jsonDiskCache->get('not timed off data'), 6789);
    }

    /**
     * @depends testGlobalValidCacheTime
     */
    public function testThresholdCleanUp()
    {
        $this->_jsonDiskCache->setCacheFileMaxRecords(100);
        $this->_jsonDiskCache->setCacheFileCleanupThreshold(0.75);

        // set 50 random valid values
        $this->_jsonDiskCache->setValidTime(5);
        for ($i = 1; $i <= 50; $i++) {
            $this->_jsonDiskCache->set(['Valid', $i], rand(1000, 9990));
        }

        // set 26 random timed out values (1% above threshold cleanup)
        $this->_jsonDiskCache->setValidTime(1);
        for ($i = 1; $i <= 26; $i++) {
            $this->_jsonDiskCache->set(['Invalid', $i], rand(1000, 9990));
        }

        $this->assertSame($this->_jsonDiskCache->countCacheRecords(), 76, 'Total cache entries before clean up should be 76');

        sleep(2);
        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->countCacheRecords(), 50, 'Total cache after clean up should be 50');
    }

    public function testCacheOverflow()
    {
        $this->_jsonDiskCache->setCacheFileMaxRecords(100);
        $this->_jsonDiskCache->setValidTime(5);
        for ($i = 1; $i <= 101; $i++) {
            $this->_jsonDiskCache->set(['Valid', $i], rand(1000, 9990));
        }

        $this->recreateJsonObject();
        $this->assertSame($this->_jsonDiskCache->countCacheRecords(), 0, 'Cache should be zeroed after overflow');
    }

}
