<?php

declare(strict_types=1);

namespace Framework;

use Framework\Database\Connector\Mysql;
use Framework\Exceptions\{ArgumentException, ImplementationException};

class Database extends Base
{
    /**
     * @readwrite
     */
    protected string $_type;
    /**
     * @readwrite
     */
    protected array $_options;

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
            'mysql' => new Mysql($this->_options),
            default => throw new ArgumentException('Nieprawidłowy typ.'),
        };
    }


}