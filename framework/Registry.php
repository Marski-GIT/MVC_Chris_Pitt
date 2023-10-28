<?php

declare(strict_types=1);

namespace Framework;

class Registry
{
    private static array $_instances = [];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function get(string $key, mixed $default = null)
    {
        if (array_key_exists($key, self::$_instances)) {
            return self::$_instances[$key];
        }
        return $default;
    }

    public static function set(string $key, mixed $instance = null): void
    {
        self::$_instances[$key] = $instance;
    }

    public static function erase(string $key): void
    {
        unset(self::$_instances[$key]);
    }

}