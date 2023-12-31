<?php

declare(strict_types=1);

namespace Framework;

use stdClass;

final class ArrayMethods
{
    public static function toObject(array $array): stdClass
    {
        $result = new stdClass();

        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $result->{$key} = self::toObject($value);
            } else {
                $result->{$key} = $value;
            }
        }

        return $result;
    }

    public static function clean(array $array): array
    {
        return array_filter($array, fn($item) => !empty($item));
    }

    public static function trim(array $array): array
    {
        return array_map(fn($item) => trim($item), $array);
    }

    public static function flatten(array $array, $return = [])
    {
        foreach ($array as $value) {
            if (is_array($value) || is_object($value)) {
                $return = self::flatten($value, $return);
            } else {
                $return[] = $value;
            }
        }
        return $return;
    }

    public static function last($array)
    {
        if (sizeof($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[sizeof($keys) - 1]];
    }

    public static function first($array)
    {
        if (sizeof($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[0]];
    }

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }
}