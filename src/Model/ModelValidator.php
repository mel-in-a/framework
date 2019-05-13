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

use Origin\Model\Exception\ValidatorException;
use DateTime;

class ModelValidator
{
    /**
     * Holds a reference to the model.
     */
    protected $model = null;
    /**
     * Contains the validation rules.
     */
    protected $validationRules = array();

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Sets and gets rules
     *
     * @param array $rules
     * @return void
     */
    public function rules(array $rules = null)
    {
        if ($rules === null) {
            return $this->validationRules;
        }
        foreach ($rules as $field => $params) {
            $this->setRule($field, $params);
        }
    }

    public function setRule(string $field, $params)
    {
        if (is_string($params)) {
            $params = array(
              $field => array('rule' => $params),
            );
        }
        if (isset($params['rule'])) {
            $params = array('rule1' => $params);
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
    public function validate($value, $ruleSet)
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
     
        // Validation methods here
        if (method_exists($this, $rule)) {
            return call_user_func_array(array($this, $rule), $args);
        }
       
        // Validation methods in model
        if (method_exists($this->model, $rule)) {
            return call_user_func_array(array($this->model, $rule), $args);
        }
        // Regex expressions
        if ($rule[0] === '/') {
            return $this->custom($value, $rule);
        }

        throw new ValidatorException('Unkown Validation Rule');
    }

    /**
     * Handles the on
     *
     * @param bool $create
     * @param string|bool $on null,'create','update'
     * @return bool
     */
    protected function runRule(bool $create, $on)
    {
        if ($on === null or ($create and $on ==='create') or (!$create and $on ==='update')) {
            return true;
        }
        return false;
    }

    public function validates(Entity $entity, bool $create = true)
    {
        $requiredMessage = 'This field is required';
        $notBlankMessage = 'This field cannot be left blank';
        $defaultMessage = 'Invalid value';
     
        foreach ($this->validationRules as $field => $ruleset) {
            foreach ($ruleset as $validationRule) {
                $defaults = [
                  'rule' => null, 'message' => null, 'required' => false, 'on' => null,
                ];

                $validationRule = array_merge($defaults, $validationRule);
              
                if ($validationRule['required'] and $this->runRule($create, $validationRule['on']) and !in_array($field, $entity->modified())) {
                    if ($validationRule['message'] === null) {
                        $validationRule['message'] = $requiredMessage;
                    }
                    $entity->invalidate($field, $validationRule['message']);
                    continue;
                }

                if ($validationRule['on'] and !$this->runRule($create, $validationRule['on'])) {
                    continue;
                }

                if ($validationRule['rule'] === 'isUnique') {
                    $validationRule['rule'] = ['isUnique',[$field]];
                }

                if (in_array($field, $entity->modified())) {
                    if ($validationRule['message'] === null) {
                        $validationRule['message'] = $defaultMessage;
                        if ($validationRule['rule'] === 'notBlank') {
                            $validationRule['message'] = $notBlankMessage;
                        }
                    }

                    $value = $entity->get($field);
                  
                    // Invalidate objects or arrays e.g datetime fields, or other objects - fall back
                    if (is_object($value) or is_array($value)) {
                        $entity->invalidate($field, $defaultMessage);
                        continue;
                    }
                 
                    if ($validationRule['rule'] === 'notBlank') {
                        if (!$this->validate($value, $validationRule['rule'])) {
                            $entity->invalidate($field, $validationRule['message']);
                        }
                        continue;
                    }

                    if ($value === '' or $value === null) {
                        continue;
                    }

                    if (is_array($validationRule['rule']) and $validationRule['rule'][0] === 'isUnique') {
                        $value = $entity;
                    }
                
                    if (!$this->validate($value, $validationRule['rule'])) {
                        $entity->invalidate($field, $validationRule['message']);
                    }
                }
            }
        }
      
        return empty($entity->errors());
    }

    /**
     * VALIDATORS.
     */
    public function alphaNumeric($value)
    {
        return ctype_alnum($value);
    }

    public function boolean($value)
    {
        return is_bool($value);
    }

    public function custom($value, $regex)
    {
        return (bool) preg_match($regex, $value);
    }

    /**
     * Validates datetime using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat Y-m-d
     *
     * @return bool
     */
    public function date($value, $dateFormat = 'Y-m-d')
    {
        $dateTime = DateTime::createFromFormat($dateFormat, $value);
        if ($dateTime !== false and $dateTime->format($dateFormat) === $value) {
            return true;
        }

        return false;
    }

    /**
     * Validates datetime using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat Y-m-d H:i:s
     *
     * @return bool
     */
    public function datetime($value, $dateFormat = 'Y-m-d H:i:s')
    {
        $dateTime = DateTime::createFromFormat($dateFormat, $value);
        if ($dateTime !== false and $dateTime->format($dateFormat) === $value) {
            return true;
        }

        return false;
    }

    public function decimal($value, $options = null)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false and !is_integer($value);
    }

    /**
     * Smooth email validation.
     */
    public function email($value, $options = null)
    {
        return (bool) preg_match('/[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(.[a-zA-Z0-9-.])+/', $value);
    }

    public function equalTo($value, $comparedTo = null)
    {
        return $value === $comparedTo;
    }

    /**
     * Checks that value has an extension.
     *
     * @param string       $value
     * @param string|array $extensions [description]
     *
     * @return bool true or false
     */
    public function extension($value, $extensions = [])
    {
        if (is_string($extensions)) {
            $extensions = [$extensions];
        }
        $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION));

        return $this->inList($extension, $extensions, true);
    }

    public function inList($value, $values, $caseInSensitive = false)
    {
        if ($caseInSensitive) {
            $values = array_map('mb_strtolower', $values);

            return in_array(mb_strtolower($value), $values);
        }

        return in_array($value, $values);
    }

    public function ip($value, $options = null)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * Checks if string is less than or equals to the max length.
     */
    public function maxLength($value, $max)
    {
        return mb_strlen($value) <= $max;
    }

    /**
     * Checks if a string is greater or equal to the min length.
     */
    public function minLength($value, $min)
    {
        return mb_strlen($value) >= $min;
    }

    /**
     * Checks if a string is not blank (not empty and not made up of whitespaces).
     */
    public function notBlank($value)
    {
        if (empty($value) and (string) $value !== '0') {
            return false;
        }

        return (bool) preg_match('/[^\s]+/', $value);
    }

    /**
     * Checks that value is not empty whilst dealing with 0 values.
     */
    public function notEmpty($value)
    {
        if (empty($value) and (string) $value !== '0') {
            return false;
        }

        return true;
    }

    public function numeric($value)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_INT);
    }

    public function range($value, $min = null, $max = null)
    {
        if (!is_numeric($value) or !isset($min) or !isset($max)) {
            return false;
        }

        return $value >= $min and $value <= $max;
    }

    /**
     * Validates date using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat H:i:s
     *
     * @return bool
     */
    public function time($value, $timeFormat = 'H:i:s')
    {
        $dateTime = DateTime::createFromFormat($timeFormat, $value);
        if ($dateTime !== false and $dateTime->format($timeFormat) === $value) {
            return true;
        }

        return false;
    }

    /**
     * Checks that a url is valid.
     *
     * @param string $url
     * @param bool   $protocol set to false if you want a valid url not to include the protocol
     *
     * @return bool true or false
     */
    public function url($url, $protocol = true)
    {
        if ($protocol) {
            return (bool) filter_var($url, FILTER_VALIDATE_URL);
        }

        if (preg_match('/^http|https|:\/\//i', $url)) {
            return false;
        }

        return (bool) filter_var('https://'.$url, FILTER_VALIDATE_URL);
    }
}