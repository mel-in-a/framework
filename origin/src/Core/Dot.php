<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Core;

/**
 * Dot Class
 * This is for handling arrays paths which are defined by dot notations.
 *
 *  array('Asset' => array(
 *            'Framework' => array(
 *                'js' => 'origin.js'
 *                )
 *             )
 *       );
 *
 * Would be expressed as Asset.Framework.js
 *
 * $Dot = new Dot($someArray)
 * $Dot->set('App.someSetting',123)
 * $app = $Dot->get('App') // array('App'=>array('someSetting'=>123))
 * or $someSetting = $Dot->get('App.someSetting');
 */
class Dot
{
    /**
     * The items in the array to mainuplate.
     *
     * @var array
     */
    public $items = array();

    /**
     * Set the items to use or leave blank.
     *
     * @param array $items array of items to play with
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Sets an item in the path.
     *
     * @param string $key   Project, or Project.setting
     * @param mixed  $mixed string,int,array
     */
    public function set($key, $value)
    {
        $items = &$this->items;
        foreach (explode('.', $key) as $key) {
            if (!isset($items[$key]) or !is_array($items[$key])) {
                $items[$key] = [];
            }
            $items = &$items[$key];
        }
        $items = $value;
    }

    /**
     * Gets an item in the path.
     *
     * @param string $key Project, or Project.setting
     * @param $defaulValue this is what to return if not found e.g null, '' or array()
     *
     * @return mixed key value
     */
    public function get($key, $defaultValue = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }
        if (strpos($key, '.') === false) {
            return $defaultValue;
        }

        $items = $this->items;
        foreach (explode('.', $key) as $path) {
            if (!is_array($items) or !array_key_exists($path, $items)) {
                return $defaultValue;
            }
            $items = &$items[$path];
        }

        return $items;
    }

    /**
     * Returns the items array.
     *
     * @return array $items
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Deletes an item in the path.
     *
     * @param string $key Project, or Project.setting
     */
    public function delete($key)
    {
        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);

            return true;
        }
        if (strpos($key, '.') === false) {
            return false;
        }

        $items = &$this->items;
        $paths = explode('.', $key);
        $lastPath = array_pop($paths);

        foreach ($paths as $path) {
            if (!is_array($items) or !array_key_exists($path, $items)) {
                continue;
            }
            $items = &$items[$path];
        }
        if (isset($items[$lastPath])) {
            unset($items[$lastPath]);

            return true;
        }

        return false;
    }

    /**
     * Checks item in the path.
     *
     * @param string $key Project, or Project.setting
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }
        if (strpos($key, '.') === false) {
            return false;
        }
        $items = &$this->items;
        $paths = explode('.', $key);
        $lastPath = array_pop($paths);

        foreach ($paths as $path) {
            if (!is_array($items) or !array_key_exists($path, $items)) {
                continue;
            }
            $items = &$items[$path];
        }
        if (isset($items[$lastPath])) {
            return true;
        }

        return false;
    }
}
