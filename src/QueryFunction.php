<?php

namespace Nahid\QArray;

class QueryFunction
{
    protected static $_functions = [
        'date'  => 'date',
        'year'  => 'year',
        'month'  => 'month',
        'unix_time'  => 'unixTime',
        'unix_date'  => 'unixDate',
        'count'  => 'total',
        'lowercase'  => 'lowercase',
        'uppercase'  => 'uppercase',
        'round'  => 'round',
        'ceil'  => 'ceil',
        'floor'  => 'floor',
        'sqrt'  => 'sqrt',
        'sin'  => 'sin',
        'cos'  => 'cos',
    ];


    public static function unixTime($value)
    {
        return strtotime($value);
    }

    public static function unixDate($value)
    {
        return strtotime(date('Y-m-d', strtotime($value)));
    }

    public static function date($value)
    {
        $time = is_int($value) ? $value : strtotime($value);
        return date('Y-m-d', $time);
    }

    public static function year($value)
    {
        return date('Y', strtotime($value));
    }

    public static function month($value)
    {
        return date('m', strtotime($value));
    }

    public static function total($value)
    {
        if (is_array($value)) {
            return count($value);
        }
        return 0;
    }

    public static function lowercase($value)
    {
        return strtolower($value);
    }

    public static function uppercase($value)
    {
        return strtoupper($value);
    }

    public static function round($value)
    {
        return round($value, 1);
    }

    public static function ceil($value)
    {
        return ceil($value);
    }

    public static function floor($value)
    {
        return floor($value);
    }

    public static function sqrt($value)
    {
        return sqrt($value);
    }

    public static function sin($value)
    {
        return sin($value);
    }

    public static function cos($value)
    {
        return cos($value);
    }

    public static function hasFunction($func)
    {
        return isset(static::$_functions[$func]) ? static::$_functions[$func] : false;
    }


    public static function register($name, callable $func)
    {
        if (!array_key_exists($name, static::$_functions)) {
            static::$_functions[$name] = $func;
            return true;
        }

        return false;
    }
}