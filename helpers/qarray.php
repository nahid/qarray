<?php

use Nahid\QArray\QueryEngine;
use Nahid\QArray\ArrayQuery;

if (!function_exists('convert_to_array')) {
    function convert_to_array($data)
    {
        return \Nahid\QArray\Utilities::toArray($data);
    }
}

if (!function_exists('qarray')) {
    /**
     * @param $data
     * @return \Nahid\QArray\QueryEngine
     */
    function qarray($data = [])
    {
        return \Nahid\QArray\Utilities::qarray($data);
    }
}