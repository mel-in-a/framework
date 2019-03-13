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

namespace Origin\Model;

use Origin\Core\Inflector;
use Origin\Core\Configure;

/*
 * In computer science, marshalling (similar to serialization) is the process
 * of transforming the memory representation of an object to a data format suitable
 * for storage or transmission. It is typically used when data must be moved
 * between different parts of a computer program or from one program to another.
 * Source: wikipedia.
 */
/*
 * 23.12.18 Moved toArray to entity and moved model newEntity/Patch entity here. So that
 * we can access model information when transforming entities. E.g date time fields etc.
 *
 * Marshaller is used to process request data as it converts localized fields such as dates,
 * decimals etc.
 */
use Origin\Utility\Date;
use Origin\Utility\Number;
use Origin\Model\Model;

class Marshaller
{
    protected $model = null;

    protected $autoParse = true;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->autoParse = Configure::read('I18n.parse');
    }

    /**
     * Creates a map for entity fields.
     * Example:
     *  ['author' => 'one', 'tags' => 'many']
     *
     * @return array
     */
    protected function buildAssociationMap()
    {
        $map = [];
        $model = $this->model;
        foreach (array_merge($model->hasOne, $model->belongsTo) as $alias => $config) {
            $map[lcfirst($alias)] = 'one';
        }
        foreach (array_merge($model->hasMany, $model->hasAndBelongsToMany) as  $alias => $config) {
            $key = Inflector::pluralize(lcfirst($alias));
            $map[$key] = 'many';
        }

        return $map;
    }

    /**
     * Parses data from known models
     *
     * @param array $data
     */
    protected function parseData(array $data, string $model = null)
    {
        if ($model === null) {
            return $data;
        }
        if ($model === $this->model->name) {
            $schema = $this->model->schema();
        } else {
            $schema = $this->model->{$model}->schema();
        }
   
        foreach ($data as $field => $value) {
            if (!is_string($field) or !isset($schema[$field])) {
                continue;
            }
            if ($value === '' or $value === null) {
                continue;
            }
            $column = $schema[$field];
            
            $valueToSet = null;

            switch ($column['type']) {
                case 'datetime':
                    /*
                     * If the field stays an array then its because its invalid
                     */
                    if (is_array($value)) {
                        if (empty($value['date']) and empty($value['time'])) {
                            $data[$field] = null;
                        } elseif (!empty($value['date']) and !empty($value['time'])) {
                            $date = Date::parseDate($value['date']);
                            $time = Date::parseTime($value['time']);
                            if ($date and $time) {
                                $valueToSet = $date . ' ' . $time;
                            }
                        }
                    } elseif (is_string($value)) {
                        $valueToSet = Date::parseDatetime($value);
                    }

                break;
                case 'date':
                    $valueToSet = Date::parseDate($value);
                break;
                case 'time':
                    $valueToSet =  Date::parseTime($value);
                break;
                case 'decimal':
                    $valueToSet = Number::parseDecimal($value);
                break;
                case 'float':
                     $valueToSet = Number::parseFloat($value);
                break;
                case 'integer':
                    $valueToSet = Number::parseInteger($value);
                break;
            }
            if ($valueToSet !== null) {
                $data[$field] = $valueToSet;
            }
        }

        return $data;
    }

    /**
     * Creates One Entity
     *
     * Options
     * - name: model name
     * - parse: parse date/number/time/datetime strings
     *
     * @param array $data
     * @param array $options
     * @return Entity
     */
    public function one(array $data, array $options=[])
    {
        $options += ['name' => null,'parse'=>true];

        $propertyMap = $this->buildAssociationMap($options);
     
        if ($this->autoParse and $options['parse']) {
            $data = $this->parseData($data, $options['name']);
        }
   
        $entity = new Entity([], $options);
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                $entity->set($property, $this->{$propertyMap[$property]}($value, ['name'=>ucfirst($alias)]));
            } else {
                $entity->set($property, $value);
            }
        }
        return $entity;
    }
    
    /**
     * Handles the hasMany and hasAndBelongsToMany
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function many(array $data, array $options=[])
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = $this->one($row, $options);
        }
        return $result;
    }


    /**
     * Patches an existing entity, keeping track on changed fields (used by set, not actual value), this so
     * when saving existing entities, we don't save non submited data
     *
     * @param Entity $entity
     * @param array  $data
     * @return Entity
     */
    public function patch(Entity $entity, array $data, array $options=[])
    {
        $options += ['name' => $entity->name(),'parse'=>true];
        
        $entity->reset(); // reset modified

        $propertyMap = $this->buildAssociationMap($options);
     
        if ($this->autoParse and $options['parse']) {
            $data = $this->parseData($data, $options['name']);
        }
        
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property]) and is_array($value)) {
                $alias = $property;
                if ($propertyMap[$property] === 'many') {
                    $alias = Inflector::singularize($alias);
                }
                $entity->set($property, $this->{$propertyMap[$property]}($value, ['name'=>ucfirst($alias)]));
            } else {
                $entity->set($property, $value);
            }
        }
        return $entity;
    }
}
