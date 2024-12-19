<?php

declare(strict_types=1);

namespace Nahid\QArray;

class Func
{
    public static function column(string $name, array $data): mixed
    {
        return Utilities::arrayGet($data, $name);
    }

    public static function expr(mixed $expr): mixed
    {
        return $expr;
    }

    public static function _(mixed $expr): mixed
    {
        return static::expr($expr);
    }

    public static function uppercase(string $value): string
    {
        return strtoupper($value);
    }

    public static function lowercase(string $value): string
    {
        return strtolower($value);
    }

    public static function titlecase(string $value): string
    {
        return ucwords($value);
    }

    public static function length(string $value): int
    {
        return strlen($value);
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

    public static function reverse(string $value): string
    {
        return strrev($value);
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

    public static function replace(string $value, string $search, string $replace): string
    {
        return str_replace($search, $replace, $value);
    }

    public static function floor(float $value): int
    {
        return (int)floor($value);
    }

    public static function ceil(float $value): int
    {
        return (int)ceil($value);
    }

    public static function round(float $value, int $precision = 0): float
    {
        return round($value, $precision);
    }

    public static function integer(int|float $value): int
    {
        return (int)$value;
    }

    public static function float(int|float $value): float
    {
        return (float)$value;
    }

    public static function date_format(string $date, string $format = 'Y-m-d'): string
    {
        return date($format, strtotime($date));
    }

    public static function date_diff_days(string $date1, string $date2): int
    {
        $diff = strtotime($date1) - strtotime($date2);
        return abs(round($diff / 86400));
    }

    public static function date(string $format = 'Y-m-d m:h:i'): string
    {
        return date($format);
    }

}
