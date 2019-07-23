<?php
use Origin\Cache\Cache;
use Origin\Core\Plugin;
use Origin\Core\Configure;

/*
 * This will go in your server.php file once your app has been developed.
 */
Configure::write('debug', true); // goes in server

/*
 * If you change namespace name then you will need to change:
 *  1. The namespace declaration in all your files in the src folder
 *  2. config/autoloader.php folder
 */
Configure::write('App.namespace', 'App');
Configure::write('App.encoding', 'UTF-8');
Configure::write('Session.timeout', 3600);

Cache::config('default', ['engine' => 'File']);

/**
 * Generate a random string such as md5(time()) and place
 * here. This is used with hashing and key generation by Security.
 */
Configure::write('Security.pepper', '-----ORIGIN PHP-----');
Configure::write('Cookie.key', md5('-----ORIGIN PHP-----')); // For testing
/*
 * Load your plugins here
 * @example Plugin::load('ContactManager');
 */
Plugin::initialize();
Plugin::load('Make'); // This is for code gen you can remove
