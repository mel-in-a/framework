<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Model;

use Origin\Exception\Exception;

class Schema
{
    protected $mapping = array(
            'string' => array('name' => 'VARCHAR', 'length' => 255),
            'text' => array('name' => 'TEXT'),
            'integer' => array('name' => 'INT'),
            'float' => array('name' => 'FLOAT', 'length' => 10, 'precision' => 0), // mysql defaults
            'decimal' => array('name' => 'DECIMAL', 'length' => 10, 'precision' => 0),
            'datetime' => array('name' => 'DATETIME'),
            'date' => array('name' => 'DATE'),
        'time' => array('name' => 'TIME'),
            'binary' => array('name' => 'BLOB'),
            'boolean' => array('name' => 'TINYINT', 'length' => 1),
        );

    public function mapping(string $column)
    {
        if (isset($this->mapping[$column])) {
            return $this->mapping[$column];
        }

        return null;
    }

    /**
     * Creates an MySQL create table statement.
     *
     * @param string $table
     * @param array  $data
     *
     * array(
     *      'id' => array('type' => 'integer', 'key' => 'primary'),
     *        'title' => array(
     *          'type' => 'string',
     *           'length' => 120,
     *            'null' => false
     *           ),
     *           'body' => 'text',
     *           'published' => array(
     *             'type' => 'integer',
     *             'default' => '0',
     *             'null' => false
     *           ),
     *           'created' => 'datetime',
     *           'modified' => 'datetime'
     *       );
     *
     * @return string
     */
    public function createTable(string $table, array $data)
    {
        $result = [];

        foreach ($data as $field => $settings) {
            if (is_string($settings)) {
                $settings = ['type' => $settings];
            }

            $mapping = $this->mapping($settings['type']);
            if (!$mapping) {
                throw new Exception("Unkown column type '{$setting['type']}'");
            }

            $settings = $settings + $mapping;

            $output = "{$field} {$mapping['name']}";

            if (isset($settings['length'])) {
                if (in_array($settings['type'], ['decimal', 'float'])) {
                    $output .= "({$settings['length']},{$settings['precision']})";
                } else {
                    $output .= "({$settings['length']})";
                }
            }

            if (isset($settings['default'])) {
                $output .= " DEFAULT {$settings['default']}";
            }

            if (isset($settings['null'])) {
                if ($settings['null'] == true) {
                    $output .= ' NULL';
                } else {
                    $output .= ' NOT NULL';
                }
            }

            // When key is set as primary we automatically make it autoincrement
            if (isset($settings['key']) and $settings['key'] === 'primary') {
                $output .= ' AUTO_INCREMENT PRIMARY KEY';
            }
            $result[] = ' '.$output;
        }

        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n)";
    }
}
