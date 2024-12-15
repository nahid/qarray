<?php

namespace Nahid\QArray;

class Func
{
    public static function column(string $name, array $data): mixed
    {
        return Utilities::arrayGet($data, $name);
    }
    public static function upper(string $value): string
    {
        return strtoupper($value);
    }

    public static function lower(string $value): string
    {
        return strtolower($value);
    }

    public static function concat(string ...$vals): string
    {
        return implode('', $vals);
    }

    public static function sum(int|float ...$vals): int|float
    {
        return array_sum($vals);
    }

    public static function substring(string $value, int $start, int $length = null): string
    {
        return substr($value, $start, $length);
    }

    public static function camelCase(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
    }

    public static function snakeCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    public static function kebabCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
    }

    public static function titleCase(string $value): string
    {
        return ucwords($value);
    }

    public static function emptyToNull(string $value): string|null
    {
        return empty($value) ? null : $value;
    }

    public static function nullToEmpty(string|null $value): string
    {
        return $value ?? '';
    }

    public static function default(string|null $value, string $default): string
    {
        return $value ?? $default;
    }

}
