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

namespace Origin\I18n;

use NumberFormatter;

class Number
{
    /**
     * Holds the default Locale use by the number formatter.
     *
     * @var string
     */
    protected static $locale = 'en_US';

    /**
     * Holds the default Currency which used by Number::currency if set.
     *
     * @var string
     */
    protected static $currency = 'USD';

    /**
     * Intializes the date Object.
     *
     * @param array $config locale|timezone
     */
    public static function initialize(array $config = [])
    {
        if (isset($config['locale'])) {
            self::setLocale($config['locale']);
        }
        if (isset($config['currency'])) {
            self::setCurrency($config['currency']);
        }
    }

    /**
     * Sets the locale to be used
     *
     * @param string $locale en_US en_GB etc
     */
    public static function setLocale(string $locale)
    {
        self::$locale = $locale;
    }

    /**
     * Sets the default currency.
     *
     * @param string $locale EUR|USD|GBP
     */
    public static function setCurrency(string $currency)
    {
        self::$currency = $currency;
    }

    /**
     * Formats a number into a currency.
     *
     * @param float  $value
     * @param string $currency EUR,
     * @param array  $options  precision|places|before|after|pattern
     *
     * @return string result $1,234.56
     */
    public static function currency(float $value, string $currency = null, array $options = [])
    {
        if ($currency === null) {
            $currency = self::$currency;
        }

        return static::format($value, ['type' => NumberFormatter::CURRENCY, 'currency' => $currency] + $options);
    }

    /**
     * Formats a number to a percentage.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern|multiply
     *
     * @return string 75.00%
     */
    public static function percent(float $value, int $precision = 2, array $options = [])
    {
        if (!empty($options['multiply'])) {
            $value = $value * 100;
        }

        return static::format($value, ['precision' => $precision] + $options).'%';
    }

    /**
     * Formats a floating point number.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern
     *
     * @return string 1234.56
     */
    public static function decimal(float $value, int $precision = 2, array $options = [])
    {
        return static::format($value, ['precision' => $precision] + $options);
    }

    /**
     * Formats a number. This is used by other functions.
     *
     * @param float $value
     * @param array $options precision|places|before|after|pattern
     *
     * @return string 1234.56
     */
    public static function format($value, array $options = [])
    {
        $defaults = [
            'type' => NumberFormatter::DECIMAL, 'before' => null, 'after' => null,
        ];
        $options = array_merge($defaults, $options);

        if ($options['type'] === NumberFormatter::CURRENCY) {
            $formatted = static::formatter($options)->formatCurrency($value, $options['currency']);
        } else {
            $formatted = static::formatter($options)->format($value);
        }

        return $options['before'].$formatted.$options['after'];
    }

    /**
     * Parses a localized string
     * Use case converting user input.
     *
     * @example 123,456,789.25 -> 123456789.25
     * @param string $string
     * @param integer $format  NumberFormatter::DECIMAL, NumberFormatter::INT_32
     * @return string
     */

    public static function parse(string $string, $type = NumberFormatter::DECIMAL)
    {
        $formatter = new NumberFormatter(static::$locale, $type);
        return $formatter->parse($string);
    }

    public static function parseDecimal(string $string)
    {
        return static::parse($string, NumberFormatter::DECIMAL);
    }

    public static function parseInteger(string $string)
    {
        return static::parse($string, NumberFormatter::TYPE_INT32);
    }

    public static function parseFloat(string $string)
    {
        return static::parse($string, NumberFormatter::TYPE_DOUBLE);
    }

    /**
     * Creates a NumberFormatter object and sets the attributes.
     */
    protected static function formatter(array $options = [])
    {
        $locale = static::$locale;
        if (isset($options['locale'])) {
            $locale = $options['locale'];
        }
        $formatter = new NumberFormatter($locale, $options['type']);
        // Minimum decmial places
        if (isset($options['places'])) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['places']);
        }

        // Maximum decimal places
        if (isset($options['precision'])) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $options['precision']);
        }
        // http://php.net/manual/en/numberformatter.setpattern.php
        if (isset($options['pattern'])) {
            $formatter->setPattern($options['pattern']);
        }

        return $formatter;
    }
}