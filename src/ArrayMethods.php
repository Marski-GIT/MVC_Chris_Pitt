<?php

declare(strict_types=1);

namespace Framework;
final class ArrayMethods
{

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