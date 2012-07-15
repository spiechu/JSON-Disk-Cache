# JSON Disk Cache

Cache class intended to keep serialized data in JSON format files.

There is one cache directory per all domains. `hashtable.cache` keeps cache hashes of all domains. `domain.cache` keeps hashes with serialized data.

## Features

Class caches data on disk using JSON format. Uses typical methods `set()`, `get()`, `clear()`.

Caches are grouped in domains. Each domain is contained in one file. Hashtable with pairs: name and (optional) params are stashed in file.

What is diffrent is method `getSet` which accepts array with object, method name and optionable params to execute when cache is not present.

Additionally any set cache has its own valid time, which can be set globally or per `set()`.

## Requirements

You need at least PHP 5.4 since library uses shorthand array creation `[]` and `(new Object)->method()` construction.
If You want to run unit tests, PHPUnit 3.6 is needed.

## Installation

Library needs to be registered for autoload. It uses standard SplClassLoader, for example:

```php
<?php
require_once 'SplClassLoader.php';
$classLoader = new SplClassLoader('Spiechu\JSONDiskCache' , 'src');
$classLoader->register();
```

## Usage

At creation of `JSONDiskCache` object You can set cache directory in first param, otherwise `jsoncache` in JSONDiskCache.php directory will be set. Second param is default domain. Then You can set default cache valid time. Example below:

```php
<?php
$JSONDiskCache = new JSONDiskCache(__DIR__ . DIRECTORY_SEPARATOR . 'mycachedir', 'my_domain');

// set global cache expiration time in seconds
$JSONDiskCache->setValidTime(10);
```

There are a few ways to set cache entry:

```php
<?php
$JSONDiskCache->set('valueName', 'value to cache');

// additionally You can set value expiration time in seconds
// global cache expiration is suppressed
$JSONDiskCache->set('valueName', 'value to cache for 5 seconds', 5);

// the same cache name can be distinguished by used params
// in this case You can pass an array to set()
$JSONDiskCache->set(['valueName', 'myParam'], 'value to cache for 5 seconds', 5);
```

Now You can retrieve value from cache, notice that null value is treated as no cache at all:

```php
<?php
$cachedValue = $JSONDiskCache->get('valueName');

// when array has been passed to set(), the same construction is needed in get()
$cachedValue = $JSONDiskCache->get(['valueName', 'myParam']);
```

You can also clear cache, true means cache was found and deleted:

```php
<?php
$isCleared = $JSONDiskCache->clear('valueName');

// and again with param
$isCleared = $JSONDiskCache->clear(['valueName', 'myParam']);
```

Suppose You have `$db` object that retrieves data from database with `$db->fetchData(1)` method. When valid cache for pair `array('dataName', 1)` is found then cache is used, otherwise `$db->fetchData(1)`:

```php
<?php
$value = JSONDiskCache->getSet(['dataName', 1], [$db, 'fetchData'], [1]);
```

To save cache to a file just do nothing. Just before object is being destroyed, its destructor will save cache to cache files. To force file write You can do:

```php
<?php

// this will work, but You lost object too
unset($JSONDiskCache);

// save only hashes
$JSONDiskCache->saveHashTableToFile();

// save cache to file
$JSONDiskCache->saveCacheToFile();
```

Before cache is written to file, some maintenance is being performed automatically when cache entries numbers are above threshold. You can always do a function call:

```php
<?php

// iterates over all domains and tries to eliminate old cache entries
$JSONDiskCache->cleanUpCache();

// clean only certain domain
$JSONDiskCache->removeOldCacheEntries('domain to clean');
```