<?php

namespace Nahid\QArray;

class Utilities
{

    public static string $_traveler = '.';

    public static function arrayGet(array $data, string $node, mixed $default = null): mixed
    {
        if ($node === '' || $node === static::$_traveler) {
            return $data;
        }

        if (isset($data[$node])) {
            return $data[$node];
        }

        if (!str_contains($node, static::$_traveler)) {
            return $default;
        }

        $items = $data;

        foreach (explode(static::$_traveler, $node) as $segment) {
            if (!isset($items[$segment])) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $items;
    }
}
