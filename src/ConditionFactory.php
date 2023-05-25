<?php

declare(strict_types=1);

namespace Nahid\QArray;

final class ConditionFactory
{
    /**
     * Simple equals
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function equal(mixed $value, mixed $comparable): bool
    {
        return $value == $comparable;
    }

    /**
     * Strict equals
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function strictEqual($value, $comparable): bool
    {
        return $value === $comparable;
    }

    /**
     * Simple not equal
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function notEqual(mixed $value, mixed $comparable): bool
    {
        return $value != $comparable;
    }

    /**
     * Strict not equal
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function strictNotEqual(mixed $value, mixed $comparable): bool
    {
        return $value !== $comparable;
    }

    /**
     * Strict greater than
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function greaterThan(mixed $value, mixed $comparable): bool
    {
        return $value > $comparable;
    }

    /**
     * Strict less than
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function lessThan(mixed $value, mixed $comparable): bool
    {
        return $value < $comparable;
    }

    /**
     * Greater or equal
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function greaterThanOrEqual(mixed $value, mixed $comparable): bool
    {
        return $value >= $comparable;
    }

    /**
     * Less or equal
     *
     * @param mixed $value
     * @param mixed $comparable
     *
     * @return bool
     */
    public static function lessThanOrEqual(mixed $value, mixed $comparable): bool
    {
        return $value <= $comparable;
    }

    /**
     * In array
     *
     * @param mixed $value
     * @param array $comparable
     *
     * @return bool
     */
    public static function in(mixed $value, mixed $comparable): bool
    {
        return (is_array($comparable) && in_array($value, $comparable));
    }

    /**
     * Not in array
     *
     * @param mixed $value
     * @param array $comparable
     *
     * @return bool
     */
    public static function notIn(mixed $value, mixed $comparable): bool
    {
        return (is_array($comparable) && !in_array($value, $comparable));
    }

    public static function inArray(mixed $value, mixed $comparable): bool
    {
        if (!is_array($value)) return false;

        return in_array($comparable, $value);
    }

    public static function inNotArray(mixed $value, mixed $comparable): bool
    {
        return !static::inArray($value, $comparable);
    }

    /**
     * Is null equal
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isNull(mixed $value, mixed $comparable): bool
    {
        return is_null($value);
    }

    /**
     * Is not null equal
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isNotNull(mixed $value, mixed $comparable): bool
    {
        return !$value instanceof KeyNotExists && !is_null($value);
    }

    public static function notExists(mixed $value, mixed $comparable): bool
    {
        return $value instanceof KeyNotExists;
    }

    public static function exists(mixed $value, mixed $comparable): bool
    {
        return !static::notExists($value, $comparable);
    }

    public static function isBool(mixed $value, mixed $comparable): bool
    {
        return is_bool($comparable);
    }

    /**
     * Start With
     *
     * @param mixed $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function startWith(mixed $value, mixed $comparable): bool
    {
        if (is_array($comparable) || is_array($value) || is_object($comparable) || is_object($value)) {
            return false;
        }

        if (preg_match("/^$comparable/", $value)) {
            return true;
        }

        return false;
    }

    /**
     * End with
     *
     * @param mixed $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function endWith(mixed $value, mixed $comparable): bool
    {
        if (is_array($comparable) || is_array($value) || is_object($comparable) || is_object($value)) {
            return false;
        }

        if (preg_match("/$comparable$/", $value)) {
            return true;
        }

        return false;
    }

    /**
     * Match with pattern
     *
     * @param mixed $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function match(mixed $value, mixed $comparable): bool
    {
        if (is_array($comparable) || is_array($value) || is_object($comparable) || is_object($value)) {
            return false;
        }

        $comparable = trim($comparable);

        if (preg_match("/^$comparable$/", $value)) {
            return true;
        }

        return false;
    }

    /**
     * Contains substring in string
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function contains(mixed $value, mixed $comparable): bool
    {
        return str_contains($value, $comparable);
    }

    /**
     * Dates equal
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function dateEqual(mixed $value, mixed $comparable, string $format = 'Y-m-d'): bool
    {
        $date = date($format, strtotime($value));
        return $date == $comparable;
    }


    /**
     * is given value instance of value
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function instance(mixed $value, mixed $comparable): bool
    {
        return $value instanceof $comparable;
    }

    /**
     * is given value data type of value
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function type(mixed $value, mixed $comparable): bool
    {
        return gettype($value) === $comparable;
    }

    /**
     * is given value exits in given key of array
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function any(mixed $value, mixed $comparable): bool
    {
        if (is_array($value)) {
            return in_array($comparable, $value);
        }

        return false;
    }

    /**
     * is given value exits in given key of array
     *
     * @param string $value
     * @param string $comparable
     *
     * @return bool
     */
    public static function execFunction(mixed $value, mixed $comparable): bool
    {
        if (is_array($value)) {
            return in_array($comparable, $value);
        }

        return false;
    }
}
