<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

use Origin\Cache\Cache;
use Origin\Core\Config;

define('START_TIME', microtime(true));

/*
# This being removed
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('error_log', LOGS);
*/

/**
 * Load Autoloader
 */
require ORIGIN . '/src/Core/Exception/Exception.php';
require ORIGIN . '/src/Core/Autoloader.php';
require ROOT . '/vendor/autoload.php';

/**
 * Register error handler
 */
$errorHandler = (PHP_SAPI === 'cli') ? new Origin\Console\ErrorHandler() : new Origin\Http\ErrorHandler();
$errorHandler->register();

require ORIGIN . '/src/Core/functions.php';

/**
 *
 */
Cache::config('origin', [
    'engine' => 'File',
    'path' => CACHE . '/origin',
    'duration' => Config::read('debug') ? '+2 minutes' : '+24 hours',
    'prefix' => 'cache_',
    'serialize' => true
]);
