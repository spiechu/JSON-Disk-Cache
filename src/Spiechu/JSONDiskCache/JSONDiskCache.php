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

use SplFileInfo;

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

    const CACHE_FILE_EXT = 'cache';
    const CACHE_FILE_PERMS = 0600;
    const CACHE_FILE_MAX_RECORDS = 500;
    const CACHE_FILE_CLEANUP_THRESHOLD = 0.75;

    const CACHE_FILE_KEY_VALID_FOR = 1;
    const CACHE_FILE_KEY_SERIALIZED = 2;
    const CACHE_FILE_KEY_UNSERIALIZED = 3;

    /**
     * Global valid time value in seconds.
     *
     * @var integer
     */
    protected $validTime = self::DEFAULT_VALID_TIME;

    /**
     * Max records per domain (file).
     *
     * @var integer
     */
    protected $cacheFileMaxRecords = self::CACHE_FILE_MAX_RECORDS;

    /**
     * Threshold to trigger cleaning up domain.
     *
     * @var float values from 0.1 to 0.9
     */
    protected $cacheFileCleanupThreshold = self::CACHE_FILE_CLEANUP_THRESHOLD;

    /**
     * Hash keys lookup table max size.
     *
     * @var integer
     */
    protected $hashFileMaxRecords = self::HASH_FILE_MAX_RECORDS;

    /**
     * Current cache namespace domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Full path to cache dir.
     *
     * @var string
     */
    protected $cacheDir;

    /**
     * Main cache array.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Helper table to faster resolve often used hashes.
     *
     * @var array
     */
    protected $hashTable;

    /**
     * Keeps track if domain was already fetched from file.
     *
     * @var array
     */
    protected $fetchedDomains = [];

    /**
     * @var SetupFiles
     */
    protected $setupFiles;

    /**
     * @param string|null $cacheDir points to cache dir or uses default when null
     * @param string $domain default domain
     */
    public function __construct($cacheDir = null, $domain = 'global')
    {
        if ($cacheDir) {
            $this->cacheDir = $cacheDir;
        } else {
            $this->cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'jsoncache';
        }

        $this->setupFiles = new SetupFiles();
        $this->setupCacheDir();
        $this->setupHashFile();
        $this->setDomain($domain);
    }

    /**
     * Creates new dir if not exist from $this->_cacheDir path.
     *
     * @throws JSONDiskCacheException when $this->_cacheDir is not a dir or is not readable/writable
     */
    protected function setupCacheDir()
    {
        $dir = $this->setupFiles->setupCacheDir($this->cacheDir, self::CACHE_DIR_PERMS);
        if (!$dir instanceof \SplFileInfo) {
            throw new JSONDiskCacheException('Dir setup error');
        }
    }

    /**
     * Checks if hash file exists, creates it when not, reads the file contents.
     *
     * @throws JSONDiskCacheException when hash file is not file, not readable/writable
     */
    protected function setupHashFile()
    {
        $hashFile = $this->setupFiles->setupHashFile(
            new \SplFileInfo($this->cacheDir),
            self::HASH_FILE_NAME . '.' . self::CACHE_FILE_EXT,
            self::HASH_FILE_PERMS
        );
        if (!$hashFile instanceof \SplFileInfo) {
            throw new JSONDiskCacheException('Hash file setup error');
        }

        $hashFileContents = json_decode(file_get_contents($hashFile), true);
        $this->hashTable = ($hashFileContents === null) ? [] : $hashFileContents;
    }

    /**
     * Sets global valid time in seconds.
     *
     * This value is used when null in set() function
     *
     * @param  integer $time
     * @return JSONDiskCache fluent interface
     */
    public function setValidTime($time)
    {
        $this->validTime = (int)$time;

        return $this;
    }

    /**
     * Sets max records per domain (file).
     *
     * @param  integer $records
     * @return JSONDiskCache fluent interface
     */
    public function setCacheFileMaxRecords($records)
    {
        $this->cacheFileMaxRecords = (int)$records;

        return $this;
    }

    /**
     * Sets threshold to trigger cleaning up domain.
     *
     * @param  float $threshold 0.1 to 0.9
     * @return JSONDiskCache fluent interface
     */
    public function setCacheFileCleanupThreshold($threshold)
    {
        $this->cacheFileCleanupThreshold = (float)$threshold;

        return $this;
    }

    /**
     * Sets hash keys lookup table max size.
     *
     * @param  integer $records
     * @return JSONDiskCache fluent interface
     */
    public function setHashFileMaxRecords($records)
    {
        $this->hashFileMaxRecords = (int)$records;

        return $this;
    }

    /**
     * Gets current domain name.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Sets current domain, reads from cache file when domain name not read before.
     *
     * @param  string $domain domain to set
     * @param  boolean $forceFetch optional flag to force read from cache file
     * @return JSONDiskCache fluent interface
     */
    public function setDomain($domain, $forceFetch = false)
    {
        $this->domain = (string)$domain;
        $file = $this->setupCacheFile();
        if (!array_key_exists($this->domain, $this->fetchedDomains)) {
            $this->cache[$this->domain] = $this->fetchDataFromFile($file);
            $this->fetchedDomains[$this->domain] = 1;
        } elseif ($forceFetch) {
            $this->cache[$this->domain] = $this->fetchDataFromFile($file);
            $this->fetchedDomains[$this->domain]++;
        }

        return $this;
    }

    /**
     * Shorthand method to get cached value and set if cache is not valid.
     *
     * @param string|array $name cache name in current domain or array with name and params
     * @param string|array $objectAndMethod function name/array with object and method name
     *                                       to execute to retrieve the value to set
     * @param  array|null $params $params to pass to $objectAndMethod function
     * @param  integer|null $validTime $this->_validTime is used when set to null
     * @return mixed|null   value from cache or null when not found or not valid
     */
    public function getSet($name, $objectAndMethod, array $params = null, $validTime = null)
    {
        $returnValue = $this->get($name);
        if ($returnValue === null) {
            $params = $params ? : [];
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
                if (!isset($this->cache[$this->domain][$nameHash][self::CACHE_FILE_KEY_UNSERIALIZED])) {
                    $this->cache[$this->domain][$nameHash][self::CACHE_FILE_KEY_UNSERIALIZED]
                        = unserialize($this->cache[$this->domain][$nameHash][self::CACHE_FILE_KEY_SERIALIZED]);
                }

                return $this->cache[$this->domain][$nameHash][self::CACHE_FILE_KEY_UNSERIALIZED];
            }
            unset($this->cache[$this->domain][$nameHash]);
        }

        return null;
    }

    /**
     * Checks if name is present in current domain.
     *
     * @param  string|array $name cache name in current domain or array with name and params
     * @return boolean      true when name is present
     */
    public function isCachePresent($name)
    {
        return isset($this->cache[$this->domain][$this->getHashKey($name)]);
    }

    /**
     * Returns sha1 hash from name.
     *
     * @param  string|array cache name or array with name and params
     * @return string hash form $name
     */
    protected function getHashKey($name)
    {
        $name = (is_array($name)) ? implode('_', $name) : (string)$name;
        $key = array_search($name, $this->hashTable);
        if ($key === false) {
            $hashedName = sha1($name);
            $this->hashTable[$hashedName] = $name;

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

        return $this->cache[$this->domain][$name][self::CACHE_FILE_KEY_VALID_FOR] >= time();
    }

    /**
     * Sets name and value to cache.
     *
     * @param  string|array $name cache name to set or array with name and params
     * @param  mixed $value cache value to set
     * @param  integer|null $validTime $this->_validTime is used when set to null
     * @return JSONDiskCache fluent interface
     */
    public function set($name, $value, $validTime = null)
    {
        $name = $this->getHashKey($name);
        $validTime = $validTime ? : $this->validTime;
        $this->cache[$this->domain][$name][self::CACHE_FILE_KEY_VALID_FOR] = time() + $validTime;
        $this->cache[$this->domain][$name][self::CACHE_FILE_KEY_UNSERIALIZED] = $value;

        return $this;
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
            unset($this->cache[$this->domain][$this->getHashKey($name)]);

            return true;
        }

        return false;
    }

    /**
     * Make sure all values are saved.
     */
    public function __destruct()
    {
        $this->saveHashTableToFile();
        $this->saveCacheToFile();
    }

    /**
     * Saves whole hashtable to file.
     *
     * Also checks if hashtable is not too big. In such case the whole hashtable
     * is being erased.
     */
    public function saveHashTableToFile()
    {
        if (count($this->hashTable) > $this->hashFileMaxRecords) {
            $this->hashTable = [];
        }
        $fileName = $this->cacheDir . DIRECTORY_SEPARATOR . self::HASH_FILE_NAME . '.' . self::CACHE_FILE_EXT;
        file_put_contents($fileName, json_encode($this->hashTable), LOCK_EX);
    }

    /**
     * Saves all domains to separate files.
     *
     * Also checks if cache number is not over max limit or cleanup limit.
     */
    public function saveCacheToFile()
    {
        foreach ($this->cache as $domain => $cache) {
            $filename = $this->constructFullCacheFilenamePath($domain);
            $this->cache[$domain] = array_merge($this->fetchDataFromFile($filename), $this->cache[$domain]);
        }
        $this->cleanUpCache();
        foreach ($this->cache as $domain => $cache) {
            foreach ($cache as $k => $v) {
                if (isset($cache[$k][self::CACHE_FILE_KEY_UNSERIALIZED])) {
                    $cache[$k][self::CACHE_FILE_KEY_SERIALIZED]
                        = serialize($cache[$k][self::CACHE_FILE_KEY_UNSERIALIZED]);
                    unset($cache[$k][self::CACHE_FILE_KEY_UNSERIALIZED]);
                }
            }
            $filename = $this->constructFullCacheFilenamePath($domain);
            file_put_contents($filename, json_encode($cache), LOCK_EX);
        }
    }

    /**
     * Returns full path to cache file according to current domain.
     *
     * @param  string $filename
     * @return string full path to file
     */
    protected function constructFullCacheFilenamePath($filename)
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $filename . '.' . self::CACHE_FILE_EXT;
    }

    /**
     * Reads file contents and adds to cache array.
     *
     * @param  string $file full path to file to fetch from
     * @return array  fetched data
     */
    protected function fetchDataFromFile($file)
    {
        $fetchedData = json_decode(file_get_contents($file), true);

        return ($fetchedData === null) ? [] : $fetchedData;
    }

    /**
     * Iterates over all domains and tries to eliminate old cache entries.
     *
     * TODO: improve decision what to do when cache entries are still over the max acceptable limit
     * (log WARNING to increase max entries limit maybe?)
     */
    public function cleanUpCache()
    {
        foreach ($this->cache as $domain => $cache) {

            // first try to clean up old cache
            if ($this->countCacheRecords($domain)
                > intval($this->cacheFileMaxRecords * $this->cacheFileCleanupThreshold)
            ) {
                $this->removeOldCacheEntries($domain);
            }

            // if it not helps, get rid of the all cache contents in domain
            if ($this->countCacheRecords($domain) > $this->cacheFileMaxRecords) {
                $this->deleteCacheFile($domain);
                $this->cache[$domain] = [];
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
        $domain = $domain ? : $this->domain;
        if (array_key_exists($domain, $this->cache)) {
            return count($this->cache[$domain]);
        }

        return 0;
    }

    /**
     * Clean old cache records.
     *
     * @param string|null $domain domain name to cleanup or null to current domain
     */
    public function removeOldCacheEntries($domain = null)
    {
        $domain = $domain ? : $this->domain;
        foreach ($this->cache[$domain] as $k => $v) {
            if ($v[self::CACHE_FILE_KEY_VALID_FOR] < time()) {
                unset($this->cache[$domain][$k]);
            }
        }
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
     * Checks if cache file exists and is ready to read/write.
     *
     * Creates a new file when not found.
     *
     * @return SplFileInfo                 full path to cache file
     * @throws JSONDiskCacheException when not a file or is not readable/writable
     */
    protected function setupCacheFile()
    {
        $file = new SplFileInfo($this->constructFullCacheFilenamePath($this->domain));
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
}
