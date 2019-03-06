<?php
use Origin\Core\Configure;
use Origin\Core\Plugin;

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

/**
 * Generate a random string such as md5(time()) and place
 * here. This is used with hashing and key generation by Security.
 */
Configure::write('Security.salt', '-----ORIGIN PHP-----');
/*
 * Load your plugins here
 * @example Plugin::load('ContactManager');
 */

Plugin::load('Make');