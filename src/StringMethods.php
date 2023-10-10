<?php

declare(strict_types=1);

namespace Framework;

final class StringMethods
{
    private static string $_delimiter = '/';

    public static function getDelimiter(string $delimiter): string
    {
        return $delimiter;
    }

    public static function setDelimiter(string $delimiter): void
    {
        self::$_delimiter = $delimiter;
    }

    public static function match(string $string, string $pattern): array
    {
        preg_match_all(self::_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

        if (!empty($matches[2])) {
            return $matches[2];
        }

        if (!empty($matches[0])) {
            return $matches[0];
        }

        return [];
    }

    public static function split(string $string, string $pattern, $limit = 0): array|false
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        return preg_split(self::_normalize($pattern), $string, $limit, $flags);
    }

    private static function _normalize(string $pattern): string
    {
        return self::$_delimiter . trim($pattern, self::$_delimiter) . self::$_delimiter;
    }

    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

}