<?php

declare(strict_types=1);

namespace Nahid\QArray;

class Utilities
{

    public static string $_traveler = '.';

    /**
     * Get value from array by key
     *
     * @param array<string, mixed> $data
     * @param string $node
     * @param mixed $default
     * @return mixed
     */
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
