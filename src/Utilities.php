<?php

namespace Nahid\QArray;

class Utilities
{
    public static function toArray($data)
    {
        if (!is_array($data) && ! $data instanceof QueryEngine) {
            return [$data];
        }

        $new_data = [];
        foreach ($data as $key => $map) {
            if ($map instanceof QueryEngine) {
                $new_data[$key] = self::toArray($map);
            } else {
                $new_data[$key] = $map;
            }
        }

        return $new_data;
    }

    public static function qarray($data = [])
    {
        if (!is_array($data)) {
            $data = [];
        }

        $instance = ArrayQuery::getInstance();

        return $instance->collect($data);
    }
}