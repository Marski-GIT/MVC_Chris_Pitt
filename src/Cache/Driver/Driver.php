<?php

declare(strict_types=1);

namespace Framework\Cache\Driver;

use Framework\Base;
use Framework\Exceptions\ImplementationException;

abstract class Driver extends Base
{
    protected array $_parsed = [];

    public function initialize(): object
    {
        return $this;
    }

    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }
}