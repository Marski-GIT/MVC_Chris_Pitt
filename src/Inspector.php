<?php

declare(strict_types=1);

namespace Framework;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Inspector
{
    protected string $_class;
    protected array $_meta = [
        'class'      => [],
        'properties' => [],
        'methods'    => []
    ];
    protected array $_properties = [];
    protected array $_methods = [];

    /**
     * @param string $_class
     */
    public function __construct(string $_class)
    {
        $this->_class = $_class;
    }

    /**
     * @throws ReflectionException
     */
    public function getClassMeta()
    {
        if (empty($this->_meta['class'])) {

            $comment = $this->_getClassComment();

            if (empty($comment)) {
                $this->_meta['class'] = null;
            } else {
                $this->_meta['class'] = $this->_parse($comment);
            }
        }
        return $this->_meta['class'];
    }

    /**
     * @throws ReflectionException
     */
    public function getClassProperties(): array
    {
        if (empty($this->_properties)) {

            $properties = $this->_getClassProperties();

            foreach ($properties as $property) {
                $this->_properties[] = $property->getname();
            }
        }
        return $this->_properties;
    }

    /**
     * @throws ReflectionException
     */
    public function getClassMethods(): array
    {
        if (empty($this->_methods)) {

            $methods = $this->_getClassMethods();

            foreach ($methods as $method) {
                $this->_methods[] = $method->getName();
            }
        }
        return $this->_properties;
    }

    /**
     * @throws ReflectionException
     */
    public function getPropertyMeta(string $property)
    {
        if (empty($this->_meta['properties'][$property])) {

            $comment = $this->_getPropertyComment($property);

            if (empty($comment)) {
                $this->_meta['properties'][$property] = null;
            } else {
                $this->_meta['properties'][$property] = $this->_parse($comment);
            }
        }
        return $this->_meta['properties'][$property];
    }

    /**
     * @throws ReflectionException
     */
    public function getMethodMeta($method)
    {
        if (empty($this->_meta['actions'][$method])) {

            $comment = $this->_getMethodComment($method);

            if (empty($comment)) {
                $this->_meta['methods'][$method] = null;
            } else {
                $this->_meta['methods'][$method] = $this->_parse($comment);
            }
        }
        return $this->_meta['methods'][$method];
    }

    /**
     * @throws ReflectionException
     */
    protected function _getClassComment(): false|string
    {
        $reflection = new ReflectionClass($this->_class);
        return $reflection->getDocComment();
    }

    /**
     * @throws ReflectionException
     */
    protected function _getClassProperties(): array
    {
        $reflection = new ReflectionClass($this->_class);
        return $reflection->getProperties();
    }

    /**
     * @throws ReflectionException
     */
    protected function _getClassMethods(): array
    {
        $reflection = new ReflectionClass($this->_class);
        return $reflection->getMethods();
    }

    /**
     * @throws ReflectionException
     */
    protected function _getPropertyComment(string $property): false|string
    {
        $reflection = new ReflectionProperty($this->_class, $property);
        return $reflection->getDocComment();
    }

    /**
     * @throws ReflectionException
     */
    protected function _getMethodComment(string $method): false|string
    {
        $reflection = new ReflectionMethod($this->_class, $method);
        return $reflection->getDocComment();
    }

    protected function _parse(string $comment): array
    {
        $meta = [];
        $pattern = '(@[a-zA-Z+\s*[a-zA-Z0-9, ()_]*)';
        $matches = StringMethods::match($comment, $pattern);
        if (!is_null($matches)) {

            foreach ($matches as $match) {
                $parts = ArrayMethods::clean(ArrayMethods::trim(StringMethods::split($match, '[\s]', 2)));

                $meta[$parts[0]] = true;

                if (sizeof($parts) > 1) {
                    $meta[$parts[0]] = ArrayMethods::clean(ArrayMethods::trim(StringMethods::split($parts[1], ',')));
                }
            }
        }
        return $meta;
    }
}