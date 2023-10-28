<?php

declare(strict_types=1);

namespace Framework\Router\Route;

use Framework\Router\Route;

class Regex extends Route
{
    /**
     * @readwrite
     */
    protected $_key;

    public function matches(string $url): bool
    {
        $pattern = $this->_pattern;
        preg_match_all('#^' . $pattern . '$#', $url, $values);

        if (sizeof($values) && sizeof($values[0]) && sizeof($values[1])) {
            $derived = array_combine($this->_key, $values[1]);

            $this->_parameters = array_merge($this->_parameters, $derived);
            return true;
        }
        return false;
    }
}