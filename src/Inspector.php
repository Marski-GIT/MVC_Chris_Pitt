<?php

declare(strict_types=1);

namespace Framework;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

final class Inspector
{
    const PATTERN = '(@[a-zA-Z+\s*[a-zA-Z0-9, ()_]*)';
    protected object $_class;
    protected array $_meta = [
        'class'      => [],
        'properties' => [],
        'methods'    => []
    ];
    protected array $_properties = [];
    protected array $_methods = [];

    /**
     * @param object $_class
     */
    public function __construct(object $_class)
    {
        $this->_class = $_class;
    }

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

    protected function _getClassComment(): false|string
    {
        $reflection = new ReflectionClass($this->_class);
        return $reflection->getDocComment();
    }

    protected function _getClassProperties(): array
    {
        $reflection = new ReflectionClass($this->_class);
        return $reflection->getProperties();
    }

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
        $matches = StringMethods::match($comment, self::PATTERN);

        foreach ($matches as $match) {
            $parts = ArrayMethods::clean(ArrayMethods::trim(StringMethods::split($match, '[\s]', 2)));

            $meta[$parts[0]] = true;

            if (sizeof($parts) > 1) {
                $meta[$parts[0]] = ArrayMethods::clean(ArrayMethods::trim(StringMethods::split($parts[1], ',')));
            }
        }

        return $meta;
    }
}