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

    public static function hasFunction($func)
    {
        return static::$_functions[$func] ?? false;
    }


    public static function register($name, callable $func)
    {
        if (is_callable($func)) {
            static::$_functions[$name]  = $func;
        }
    }
}