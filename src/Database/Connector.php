<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Base;
use Framework\Exceptions\ImplementationException;

class Connector extends Base
{
    public function initialize(): Connector
    {
        return $this;
    }

    /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }

}