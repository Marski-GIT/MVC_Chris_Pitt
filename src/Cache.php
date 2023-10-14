<?php

declare(strict_types=1);

namespace Framework;

use Framework\Cache\Driver\Memcache;
use Framework\Exceptions\ArgumentException;
use Framework\Exceptions\ImplementationException;

class Cache extends Base
{
    /**
     * @readwrite
     */
    protected string $_type;

    /**
     * @readwrite
     */
    protected array $_options = [];

    /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }

    /**
     * @throws ArgumentException
     */
    public function initialize(): object
    {
        if (!$this->_type) {
            throw new ArgumentException('Nieprawidłowy typ.');
        }
        return match ($this->_type) {
            'memcached' => new Memcache($this->_options),
            default     => throw new ArgumentException('Nieprawidłowy typ.'),
        };
    }

}