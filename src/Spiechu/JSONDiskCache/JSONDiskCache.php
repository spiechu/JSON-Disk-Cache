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

/**
 * Cache class intended to keep serialized data in JSON format files.
 *
 * There is one cache directory where can be found one cache file per domain.
 * This prevents from mixing the same cache variable names.
 *
 * @author Dawid Spiechowicz <spiechu@gmail.com>
 * @since 0.1.0
 */
class JSONDiskCache
{

    const DEFAULT_VALID_TIME = 60;

    const CACHE_DIR_PERMS = 0700;

    const HASH_FILE_NAME = 'hashtable';
    const HASH_FILE_PERMS = 0600;
    const HASH_FILE_MAX_RECORDS = 1000;

    const CACHE_FILE_EXTENSION = 'cache';
    const CACHE_FILE_PERMS = 0600;
    const CACHE_FILE_MAX_RECORDS = 500;
    const CACHE_FILE_CLEANUP_THRESHOLD = 0.75;

    const CACHE_FILE_VALID_FOR_KEY = 1;
    const CACHE_FILE_SERIALIZED_KEY = 2;
    const CACHE_FILE_UNSERIALIZED_KEY = 3;

    /**
     * Global valid time value in seconds.
     *
     * @var integer
     */
    protected $_validTime = self::DEFAULT_VALID_TIME;

    /**
     * Max records per domain (file).
     *
     * @var integer
     */
    protected $_cacheFileMaxRecords = self::CACHE_FILE_MAX_RECORDS;

    /**
     * Threshold to trigger cleaning up domain.
     *
     * @var float values from 0.1 to 0.9
     */
    protected $_cacheFileCleanupThreshold = self::CACHE_FILE_CLEANUP_THRESHOLD;

    /**
     * Hash keys lookup table max size.
     *
     * @var integer
     */
    protected $_hashFileMaxRecords = self::HASH_FILE_MAX_RECORDS;

    /**
     * Current cache namespace domain.
     *
     * @var string
     */
    protected $_domain;

    /**
     * Full path to cache dir.
     *
     * @var string
     */
    protected $_cacheDir;

    /**
     * Main cache array.
     *
     * @var array
     */
    protected $_cache = [];

    /**
     * Helper table to faster resolve often used hashes.
     *
     * @var array
     */
    protected $_hashTable;

    /**
     * Keeps track if domain was already fetched from file.
     *
     * @var array
     */
    protected $_fetchedDomains = [];

    /**
     * @param string|null $cacheDir points to cache dir or uses default when null
     * @param string      $domain   default domain
     */
    public function __construct($cacheDir = null, $domain = 'global')
    {
        if ($cacheDir) {
            $this->_cacheDir = $cacheDir;
        } else {
            $this->_cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'jsoncache';
        }
        $this->setupCacheDir();
        $this->setupHashFile();
        $this->setDomain($domain);
    }

    /**
     * Checks if hash file exists, creates it when not, reads the file contents.
     *
     * @throws JSONDiskCacheException when hash file is not file, not readable/writable
     */
    protected function setupHashFile()
    {
        $hashFile = new \SplFileInfo(
                $this->_cacheDir . DIRECTORY_SEPARATOR
                . self::HASH_FILE_NAME . '.' . self::CACHE_FILE_EXTENSION);
        if (!file_exists($hashFile)) {
            try {
                touch($hashFile);
                chmod($hashFile, self::HASH_FILE_PERMS);
            } catch (\Exception $e) {
                throw new JSONDiskCacheException($e->getMessage());
            }
        }
        if (!$hashFile->isFile()) {
            throw new JSONDiskCacheException("{$hashFile->getFilename()} is not a file");
        }
        if (!($hashFile->isReadable() || $hashFile->isWritable())) {
            throw new JSONDiskCacheException("{$hashFile->getFilename()} is not readable or writable");
        }
        $hashFileContents = json_decode(file_get_contents($hashFile), true);
        $this->_hashTable = ($hashFileContents === null) ? [] : $hashFileContents;
    }

    /**
     * Sets global valid time in seconds.
     *
     * This value is used when null in set() function
     *
     * @param  integer       $time
     * @return JSONDiskCache fluent interface
     */
    public function setValidTime($time)
    {
        $this->_validTime = (int) $time;

        return $this;
    }

    /**
     * Sets max records per domain (file).
     *
     * @param  integer       $records
     * @return JSONDiskCache fluent interface
     */
    public function setCacheFileMaxRecords($records)
    {
        $this->_cacheFileMaxRecords = (int) $records;

        return $this;
    }

    /**
     * Sets threshold to trigger cleaning up domain.
     *
     * @param  float         $threshold 0.1 to 0.9
     * @return JSONDiskCache fluent interface
     */
    public function setCacheFileCleanupThreshold($threshold)
    {
        $this->_cacheFileCleanupThreshold = (float) $threshold;

        return $this;
    }

    /**
     * Sets hash keys lookup table max size.
     *
     * @param  integer       $records
     * @return JSONDiskCache fluent interface
     */
    public function setHashFileMaxRecords($records)
    {
        $this->_hashFileMaxRecords = (int) $records;

        return $this;
    }

    /**
     * Sets current domain, reads from cache file when domain name not read before.
     *
     * @param  string        $domain     domain to set
     * @param  boolean       $forceFetch optional flag to force read from cache file
     * @return JSONDiskCache fluent interface
     */
    public function setDomain($domain, $forceFetch = false)
    {
        $this->_domain = (string) $domain;
        $file = $this->setupCacheFile();
        if (!array_key_exists($this->_domain, $this->_fetchedDomains)) {
            $this->fetchDataFromFile($file);
            $this->_fetchedDomains[$this->_domain] = 1;
        } elseif ($forceFetch) {
            $this->fetchDataFromFile($file);
            $this->_fetchedDomains[$this->_domain]++;
        }

        return $this;
    }

    /**
     * Gets current domain name.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->_domain;
    }

    /**
     * Reads file contents and adds to cache array.
     *
     * @param string $file full path to file to fetch from
     */
    protected function fetchDataFromFile($file)
    {
        $fetchedData = json_decode(file_get_contents($file), true);
        $this->_cache[$this->_domain] = ($fetchedData === null) ? [] : $fetchedData;
    }

    /**
     * Creates new dir if not exist from $this->_cacheDir path.
     *
     * @throws JSONDiskCacheException when $this->_cacheDir is not a dir or is not readable/writable
     */
    protected function setupCacheDir()
    {
        $dir = new \SplFileInfo($this->_cacheDir);
        if (!file_exists($dir)) {
            try {
                mkdir($dir, self::CACHE_DIR_PERMS);
            } catch (\Exception $e) {
                throw new JSONDiskCacheException($e->getMessage());
            }
        }
        if (!$dir->isDir()) {
            throw new JSONDiskCacheException("{$this->_cacheDir} is not a dir");
        }
        if (!($dir->isReadable() || $dir->isWritable())) {
            throw new JSONDiskCacheException("{$this->_cacheDir} is not readable or writable");
        }
    }

    /**
     * Checks if cache file exists and is ready to read/write.
     *
     * Creates a new file when not found.
     *
     * @return string                 full path to cache file
     * @throws JSONDiskCacheException when not a file or is not readable/writable
     */
    protected function setupCacheFile()
    {
        $file = new \SplFileInfo($this->constructFullCacheFilenamePath($this->_domain));
        if (!file_exists($file)) {
            try {
                touch($file);
                chmod($file, self::CACHE_FILE_PERMS);
            } catch (\Exception $e) {
                throw new JSONDiskCacheException($e->getMessage());
            }
        }
        if (!$file->isFile()) {
            throw new JSONDiskCacheException("{$file->getFilename()} is not a file");
        }
        if (!($file->isReadable() || $file->isWritable())) {
            throw new JSONDiskCacheException("{$file->getFilename()} is not readable or writable");
        }

        return $file;
    }

    /**
     * Returns full path to cache file according to current domain.
     *
     * @param  string $filename
     * @return string full path to file
     */
    protected function constructFullCacheFilenamePath($filename)
    {
        return $this->_cacheDir . DIRECTORY_SEPARATOR . $filename . '.' . self::CACHE_FILE_EXTENSION;
    }

    /**
     * Sets name and value to cache.
     *
     * @param  string|array  $name      cache name to set or array with name and params
     * @param  mixed         $value     cache value to set
     * @param  integer|null  $validTime $this->_validTime is used when set to null
     * @return JSONDiskCache fluent interface
     */
    public function set($name, $value, $validTime = null)
    {
        $name = $this->getHashKey($name);
        $validTime = $validTime ?: $this->_validTime;
        $this->_cache[$this->_domain][$name][self::CACHE_FILE_VALID_FOR_KEY] = time() + $validTime;
        $this->_cache[$this->_domain][$name][self::CACHE_FILE_UNSERIALIZED_KEY] = $value;

        return $this;
    }

    /**
     * Gets value from cache.
     *
     * @param  string|array $name cache name in current domain or array with name and params
     * @return mixed        value from cache or null when not found or not valid
     */
    public function get($name)
    {
        if ($this->isCachePresent($name)) {
            $nameHash = $this->getHashKey($name);
            if ($this->isCacheValid($name)) {
                if (!isset($this->_cache[$this->_domain][$nameHash][self::CACHE_FILE_UNSERIALIZED_KEY])) {
                    $this->_cache[$this->_domain][$nameHash][self::CACHE_FILE_UNSERIALIZED_KEY]
                            = unserialize($this->_cache[$this->_domain][$nameHash][self::CACHE_FILE_SERIALIZED_KEY]);
                }

                return $this->_cache[$this->_domain][$nameHash][self::CACHE_FILE_UNSERIALIZED_KEY];
            }
            unset($this->_cache[$this->_domain][$nameHash]);
        }

        return null;
    }

    /**
     * Shorthand method to get cached value and set if cache is not valid.
     *
     * @param string|array $name            cache name in current domain or array with name and params
     * @param string|array $objectAndMethod function name/array with object and method name
     *                                       to execute to retrieve the value to set
     * @param  array|null   $params    $params to pass to $objectAndMethod function
     * @param  integer|null $validTime $this->_validTime is used when set to null
     * @return mixed|null   value from cache or null when not found or not valid
     */
    public function getSet($name, $objectAndMethod, array $params = null, $validTime = null)
    {
        $returnValue = $this->get($name);
        if ($returnValue === null) {
            $params = $params ?: [];
            if (is_array($objectAndMethod) || is_string($objectAndMethod)) {
                $this->set($name, call_user_func_array($objectAndMethod, $params), $validTime);
            } else {
                throw new JSONDiskCacheException('$objectAndMethod must be string or array');
            }
        } else {
            return $returnValue;
        }

        return $this->get($name);
    }

    /**
     * Checks if name is present in current domain.
     *
     * @param  string|array $name cache name in current domain or array with name and params
     * @return boolean      true when name is present
     */
    public function isCachePresent($name)
    {
        return isset($this->_cache[$this->_domain][$this->getHashKey($name)]);
    }

    /**
     * Clears cache entry.
     *
     * @param  string|array $name cache name in current domain or array with name and params
     * @return boolean      true when entry has been found and deleted
     */
    public function clear($name)
    {
        if ($this->isCachePresent($name)) {
            unset($this->_cache[$this->_domain][$this->getHashKey($name)]);

            return true;
        }

        return false;
    }

    /**
     * Returns sha1 hash from name.
     *
     * @param  string|array cache name or array with name and params
     * @return string hash form $name
     */
    protected function getHashKey($name)
    {
        $name = (is_array($name)) ? implode($name) : (string) $name;
        $key = array_search($name, $this->_hashTable);
        if ($key === false) {
            $hashedName = sha1($name);
            $this->_hashTable[$hashedName] = $name;

            return $hashedName;
        }

        return $key;
    }

    /**
     * Checks if cache value is not too old.
     *
     * @param  string|array $name cache name in current domain or array with name and params
     * @return boolean      true when cache value is valid
     */
    public function isCacheValid($name)
    {
        $name = $this->getHashKey($name);

        return $this->_cache[$this->_domain][$name][self::CACHE_FILE_VALID_FOR_KEY] >= time();
    }

    /**
     * Saves all domains to separate files.
     *
     * Also checks if cache number is not over max limit or cleanup limit.
     */
    public function saveCacheToFile()
    {
        $this->cleanUpCache();
        foreach ($this->_cache as $domain => $cache) {
            foreach ($cache as $k => $v) {
                if (isset($cache[$k][self::CACHE_FILE_UNSERIALIZED_KEY])) {
                    $cache[$k][self::CACHE_FILE_SERIALIZED_KEY]
                            = serialize($cache[$k][self::CACHE_FILE_UNSERIALIZED_KEY]);
                    unset($cache[$k][self::CACHE_FILE_UNSERIALIZED_KEY]);
                }
            }
            file_put_contents($this->constructFullCacheFilenamePath($domain), json_encode($cache), LOCK_EX);
        }
    }

    /**
     * Iterates over all domains and tries to eliminate old cache entries.
     *
     * TODO: improve decision what to do when cache entries are still over the max acceptable limit
     * (log WARNING to increase max entries limit maybe?)
     */
    public function cleanUpCache()
    {
        foreach ($this->_cache as $domain => $cache) {

            // first try to clean up old cache
            if ($this->countCacheRecords($domain)
                    > intval($this->_cacheFileMaxRecords * $this->_cacheFileCleanupThreshold)) {
                $this->removeOldCacheEntries($domain);
            }

            // if it not helps, get rid of the all cache in domain
            if ($this->countCacheRecords($domain) > $this->_cacheFileMaxRecords) {
                $this->deleteCacheFile($domain);
                $this->_cache[$domain] = [];
            }
        }
    }

    /**
     * Clean old cache records.
     *
     * @param string|null $domain domain name to cleanup or null to current domain
     */
    public function removeOldCacheEntries($domain = null)
    {
        $domain = $domain ?: $this->_domain;
        foreach ($this->_cache[$domain] as $k => $v) {
            if ($v[self::CACHE_FILE_VALID_FOR_KEY] < time()) {
                unset($this->_cache[$domain][$k]);
            }
        }
    }

    /**
     * Counts cache records in domain.
     *
     * @param $domain string|null domain name to count or null to check current domain
     * @return integer cache records
     */
    public function countCacheRecords($domain = null)
    {
        $domain = $domain ?: $this->_domain;
        if (array_key_exists($domain, $this->_cache)) {
            return count($this->_cache[$domain]);
        }

        return 0;
    }

    /**
     * Saves whole hashtable to file.
     *
     * Also checks if hashtable is not too big. In such case the whole hashtable
     * is being erased.
     */
    public function saveHashTableToFile()
    {
        if (count($this->_hashTable) > $this->_hashFileMaxRecords) {
            $this->_hashTable = [];
        }
        file_put_contents($this->_cacheDir . DIRECTORY_SEPARATOR
                . self::HASH_FILE_NAME . '.' . self::CACHE_FILE_EXTENSION, json_encode($this->_hashTable));
    }

    /**
     * Deletes cache file according to given domain.
     *
     * @param string $domain domain name
     */
    public function deleteCacheFile($domain)
    {
        $filename = $this->constructFullCacheFilenamePath($domain);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Make sure all values are saved.
     */
    public function __destruct()
    {
        $this->saveHashTableToFile();
        $this->saveCacheToFile();
    }
}

