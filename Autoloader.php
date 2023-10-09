<?php

declare(strict_types=1);

final class Autoloader
{
    /**
     * @throws Exception
     */
    public static function autoload(string $class): void
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        $file = str_replace(['\\', 'Framework/'], [DIRECTORY_SEPARATOR, ''], trim($class, '\\')) . '.php';

        $combined = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $file;

        if (file_exists($combined)) {
            require_once $combined;
        } else {
            throw new Exception('Nie znaleziono klasy.');
        }

    }
}