<?php

declare(strict_types=1);

namespace Framework;

use Framework\Configuration\Driver\Ini;
use Framework\Exceptions\ArgumentException;
use Framework\Exceptions\ImplementationException;

final class Configuration extends Base
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
            throw new ArgumentException('Nieprawidłowy argument.');
        }

        return match ($this->_type) {
            'ini'   => new Ini($this->_options),
            default => throw new ArgumentException('Nieprawidłowy typ.'),
        };
    }
}