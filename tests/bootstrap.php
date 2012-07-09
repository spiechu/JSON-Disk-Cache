<?php

/*
 * This file is part of the JSONDiskCache package.
 *
 * (c) Dawid Spiechowicz <spiechu@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Include STRICT error reporting.
 */
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../SplClassLoader.php';
$classLoader = new SplClassLoader('Spiechu\JSONDiskCache' , '../src');
$classLoader->register();
