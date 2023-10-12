<?php

declare(strict_types=1);

namespace Framework\Router;

use Framework\Base;
use Framework\Exceptions\ImplementationException;

class Route extends Base
{
    /**
     * @readwrite
     */
    protected string $_pattern;
    /**
     * @readwrite
     */
    protected $_controller;
    /**
     * @readwrite
     */
    protected $_action;

    /**
     * @readwrite
     */
    protected array $_parameters = [];

    /**
     * /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }


}