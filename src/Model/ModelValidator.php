<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Model;

use Origin\Validation\Validation;
use Origin\Model\Exception\ValidatorException;

class ModelValidator
{
    /**
     * Holds a reference to the model.
     */
    protected $model = null;
    /**
     * Contains the validation rules.
     * @var array
     */
    protected $validationRules = [];

    /**
     * @var string
     */
    protected $dateFormat = null;

    /**
     * @var string
     */
    protected $datetimeFormat = null;

    /**
     * @var string
     */
    protected $timeFormat = null;

    /**
     * @var array
     */
    protected $defaultMessageMap = [
        'notBlank' => 'This field is required',
        'mimeType' => 'Invalid mime type',
        'extension' => 'Invalid file extension',
        'upload' => 'File upload error',
    ];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Sets and gets rules
     *
     * @param array $rules
     * @return array
     */
    public function rules(array $rules = null) : array
    {
        if ($rules === null) {
            return $this->validationRules;
        }
        foreach ($rules as $field => $params) {
            $this->setRule($field, $params);
        }

        return $rules;
    }

    public function setRule(string $field, $params) :void
    {
        if (is_string($params)) {
            $params = ['rule1' => ['rule' => $params]];
        }
        if (isset($params['rule'])) {
            $params = ['rule1' => $params];
        }

        foreach ($params as $key => $value) {
            $value += [
                'rule' => null,
                'message' => null,
                'required' => false,
                'on' => null,
                'allowBlank' => false,
            ];
            if ($value['message'] === null) {
                $value['message'] = 'Invalid value';
                if ($value['rule'] === 'notBlank') {
                    $value['message'] = 'This field is required';
                }
                $rule = $value['rule'];
                if (is_array($value['rule'])) {
                    $rule = $value['rule'][0];
                }
        
                if (isset($this->defaultMessageMap[$rule])) {
                    $value['message'] = $this->defaultMessageMap[$rule];
                }
            }
           
            $params[$key] = $value;
        }
        $this->validationRules[$field] = $params;
    }
    
    /**
     * Validates a value
     *
     * @param mixed $value
     * @param string|array $ruleSet email or ['equalTo', 'origin']
     * @return bool
     */
    public function validate($value, $ruleSet) : bool
    {
       
        // ['extension',['csv','txt']]
        if (is_array($ruleSet)) {
            $rule = $ruleSet[0];
            $args = $ruleSet;
            $args[0] = $value;
        } else {
            $rule = $ruleSet;
            $args = [$value];
        }
     
        // Check validation class
        if (method_exists(Validation::class, $rule)) {
            return forward_static_call([Validation::class,$rule], ...$args);
        }

        // This is includes deprecated features but non as well
        if (method_exists($this, $rule)) {
            return call_user_func_array([$this, $rule], $args);
        }
       
        // Validation methods in model
        if (method_exists($this->model, $rule)) {
            return call_user_func_array([$this->model, $rule], $args);
        }
        // Regex expressions
        if ($rule[0] === '/') {
            return Validation::regex($value, $rule);
        }

        throw new ValidatorException('Unkown Validation Rule');
    }

    /**
     * Handles the on
     *
     * @param bool $create
     * @param string|bool|null $on null,'create','update'
     * @return bool
     */
    protected function runRule(bool $create, $on)  : bool
    {
        if ($on === null or ($create and $on === 'create') or (! $create and $on === 'update')) {
            return true;
        }

        return false;
    }

    /**
     * Validates data
     *
     * @internal Data should only be validated if it is submmited, it will cause issues.
     *
     * @param Entity $entity
     * @param boolean $create
     * @return bool
     */
    public function validates(Entity $entity, bool $create = true) : bool
    {
        $modified = $entity->modified();
        
        foreach ($this->validationRules as $field => $ruleset) {
            foreach ($ruleset as $validationRule) {
                if ($validationRule['on'] and ! $this->runRule($create, $validationRule['on'])) {
                    continue;
                }

                // Don't run validation rule on field if its not in the entity
                if (! $validationRule['required'] and in_array($field, $modified) === false) {
                    continue;
                }
            
                $value = $entity->get($field);
  
                // Required means the key must be present not wether it has a value or not
                if ($validationRule['required'] and ! in_array($field, $entity->properties())) {
                    $entity->invalidate($field, 'This field is required');
                    break; // dont run any more validation rules on this field if blank
                }

                // If its required rule (which does not exist), check and break or continue
                if ($validationRule['rule'] === 'notBlank') {
                    if (! $this->validate($value, 'notBlank')) {
                        $entity->invalidate($field, $validationRule['message']);
                    }
                    continue; // goto next rule
                }

                // If the value is not required and value is empty then don't validate
                if ($this->isBlank($value)) {
                    if ($validationRule['allowBlank'] === true) {
                        continue;
                    }
                }
         
                // Handle both types
                if ($validationRule['rule'] === 'isUnique') {
                    $validationRule['rule'] = ['isUnique',[$field]];
                }
                if (is_array($validationRule['rule']) and $validationRule['rule'][0] === 'isUnique') {
                    $value = $entity;
                }

                if ($validationRule['rule'] === 'confirm') {
                    $validationRule['rule'] = ['confirm',$entity->get($field . '_confirm')];
                }
               
                if (! $this->validate($value, $validationRule['rule'])) {
                    $entity->invalidate($field, $validationRule['message']);
                }
            }
        }

        return empty($entity->errors());
    }

    /**
     * Check if a value is considered blank for running a rule.
     * It also checks for empty file uploads
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isBlank($value) : bool
    {
        if ($value === '' or $value === null) {
            return true;
        }
        
        if (is_array($value) and isset($value['error'])) {
            return $value['error'] === UPLOAD_ERR_NO_FILE;
        }

        return false;
    }

    /**
     * Legacy rules
     * @deprecated custom, inList and notEmpty
     */

    /**
     * Custom validation rule, when
     * @codeCoverageIgnore
     * @param string $value
     * @param string $regex
     * @return boolean
     */
    public function custom($value, $regex) : bool
    {
        deprecationWarning('Validation rule `custom` has been deprecated use `regex` instead');

        return (bool) preg_match($regex, $value);
    }

    /**
     * Checks a value is in a list
     * @codeCoverageIgnore
     * @param string|int|float $value
     * @param array $values
     * @param boolean $caseInSensitive
     * @return boolean
     */
    public function inList($value, $values, $caseInSensitive = false) : bool
    {
        deprecationWarning('Validation rule `inList` has been deprecated use `in` instead');
        if ($caseInSensitive) {
            $values = array_map('mb_strtolower', $values);

            return in_array(mb_strtolower($value), $values);
        }

        return in_array($value, $values);
    }

    /**
     * Checks that value is not empty whilst dealing with 0 values.
     * @codeCoverageIgnore
     * @param mixed $value
     * @return boolean
     */
    public function notEmpty($value) : bool
    {
        deprecationWarning('Validation rule `notEmpty` has been deprecated use `notBlank` instead');
        if (empty($value) and (string) $value !== '0') {
            return false;
        }

        return true;
    }

    /**
     * This is used by the confirm rule, it checks that same value in another field e.g. password_confirm
     *
     * @param mixed $value1
     * @param mixed $value2
     * @return bool
     */
    public function confirm($value1, $value2) : bool
    {
        return (! is_null($value2) and $value1 == $value2);
    }
}
