# JSON Disk Cache

Cache class intended to keep serialized data in JSON format files.

There is one cache directory per all domains. `hashtable.cache` keeps cache hashes of all domains. `domain.cache` keeps hashes with serialized data.

## Features

Class caches data on disk using JSON format. Uses typical methods `set()`, `get()`, `clear()`.
What is diffrent is method `getSet` which accepts array with object, method name and optionable params to execute when cache is not present. 

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

At creation of `JSONDiskCache` object You can set cache directory in first param, otherwise `jsoncache` in JSONDiskCache.php directory will be set, for example:

```php
<?php
$JSONDiskCache = new JSONDiskCache(__DIR__ . DIRECTORY_SEPARATOR . 'mycachedir', 'my_domain');
$JSONDiskCache->set('valueName', 'value to cache');
```

Now You can retrieve value from cache, notice that null value is treated as no cache at all:

```php
$cachedValue = $JSONDiskCache->get('valueName');
```

You can also clear cache, true means cache was found and deleted:

```php
$isCleared = $JSONDiskCache->clear('valueName');
```

Suppose You have `$db` object that retrieves data from database with `$db->fetchData(1)` method. When valid cache for pair `array('dataName', 1)` is found then cache is used, otherwise `$db->fetchData(1)`:

```php
$value = JSONDiskCache->getSet(['dataName', 1], [$db, 'fetchData', 1]);
```