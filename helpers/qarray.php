<?php

use Nahid\QArray\QueryEngine;
use Nahid\QArray\ArrayQuery;

if (!function_exists('convert_to_array')) {
    function convert_to_array($data)
    {
        if (!is_array($data) && ! $data instanceof QueryEngine) {
            return [$data];
        }

        $new_data = [];
        foreach ($data as $key => $map) {
            if ($map instanceof QueryEngine) {
                $new_data[$key] = convert_to_array($map);
            } else {
                $new_data[$key] = $map;
            }
        }

        return $new_data;
    }
}

if (!function_exists('qarray')) {
    /**
     * @param $data
     * @return \Nahid\QArray\QueryEngine
     */
    function qarray($data = [])
    {
        if (!is_array($data)) {
            $data = [];
        }

        $instance = ArrayQuery::getInstance();

        return $instance->collect($data);
    }
}