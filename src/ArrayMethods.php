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

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }
}