<?php

use Nahid\QArray\QueryEngine;

if (!function_exists('convert_to_array')) {
    function convert_to_array($data)
    {
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