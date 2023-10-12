<?php

declare(strict_types=1);

namespace Framework\Router\Route;

use Framework\ArrayMethods;
use Framework\Router\Route;

class Simple extends Route
{
    public function matches($url): bool|int
    {
        $pattern = $this->_pattern;

        preg_match_all('#:([a-zA-Z0-9]+)#', $pattern, $keys);

        if (sizeof($keys) && sizeof($keys[0]) && sizeof($keys[1])) {
            $keys = $keys[1];
        } else {
            return preg_match_all('#^' . $pattern . '$#', $url, $url);
        }

        $pattern = preg_replace('#(:[a-zA-Z0-9]+)#', '([a-zA-Z0-9-_])', $pattern);
        preg_match_all('#^' . $pattern . '$#', $url, $values);
        if (sizeof($values) && sizeof($values[0]) && sizeof($values[1])) {
            unset($values[0]);

            $derived = array_combine($keys, ArrayMethods::flatten($values));
            $this->_parameters = array_merge($this->_parameters, $derived);
            return true;
        }
        return false;
    }

}