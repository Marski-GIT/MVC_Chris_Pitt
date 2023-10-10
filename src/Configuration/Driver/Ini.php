<?php

declare(strict_types=1);

namespace Framework\Configuration\Driver;

use Framework\ArrayMethods;
use Framework\Exceptions\ArgumentException;
use Framework\Exceptions\SyntaxException;

final class Ini extends Driver
{
    /**
     * @throws ArgumentException|SyntaxException
     */
    public function parse(string $path = '')
    {
        if (empty($path)) {
            throw new ArgumentException('Argument path jest niepoprawny');
        }
        if (!isset($this_parsed[$path])) {
            $config = [];

            $path = $path . '.ini';
            if (file_exists($path)) {

                ob_start();
                include $path;
                $string = ob_get_contents();
                ob_end_clean();
                
                $pairs = parse_ini_string($string);

                if (!$pairs) {
                    throw new SyntaxException('Nie można przetworzyć pliku konfiguracyjnego.');
                }

                foreach ($pairs as $key => $value) {
                    $config = $this->_pair($config, $key, $value);
                }
                $this->_parsed[$path] = ArrayMethods::toObject($config);

            } else {
                throw new SyntaxException('Nie odnaleziono pliku.');
            }
        }

        return $this->_parsed[$path];
    }

    protected function _pair(mixed $config, int|string $key, mixed $value)
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key, 2);

            if (empty($config[$parts[0]])) {
                $config[$parts[0]] = [];
            }
            $config[$parts[0]] = $this->_pair($config[$parts[0]], $parts[1], $value);
        } else {
            $config[$key] = $value;
        }
        return $config;
    }
}