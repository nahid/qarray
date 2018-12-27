<?php

namespace Nahid\QArray;

trait Functionable
{
    protected static $_functions = [
        'date'  => 'date',
        'year'  => 'year',
        'month'  => 'month',
        'unix_time'  => 'unixTime',
        'unix_date'  => 'unixDate',
        'count'  => 'total',
        'lowercase'  => 'lowercase',
    ];


    public function unixTime($value)
    {
        return strtotime($value);
    }

    public function unixDate($value)
    {
        return strtotime(date('Y-m-d', strtotime($value)));
    }

    public function date($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function year($value)
    {
        return date('Y', strtotime($value));
    }

    public function month($value)
    {
        return date('m', strtotime($value));
    }

    public function total($value)
    {
        if (is_array($value)) {
            return count($value);
        }
        return 0;
    }

    public function lowercase($value)
    {
        return strtolower($value);
    }

    public static function hasFunction($func)
    {
        return static::$_functions[$func] ?? false;
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