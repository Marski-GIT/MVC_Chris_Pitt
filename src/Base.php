<?php

declare(strict_types=1);

namespace Framework;

use Exception;
use Framework\Exceptions\{ArgumentException, ProperyException, ReadOnlyException, WriteOnlyException};

abstract class Base
{
    const REGEX_METHOD_NAME = '[a-zA-Z0-9]';
    readonly Inspector $_inspector;

    /**
     * @param array|object $options
     */
    public function __construct(array|object $options = [])
    {
        $this->_inspector = new Inspector($this);

        if (is_array($options) || is_object($options)) {

            foreach ($options as $key => $value) {
                $key = ucfirst($key);
                $method = "set($key)";
                $this->$method($value);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        if (empty($this->_inspector)) {
            throw new Exception('Wywołaj metodę parent::__construct!');
        }

        $getMatches = StringMethods::match($name, '^get(' . self::REGEX_METHOD_NAME . '+)$');

        if (sizeof($getMatches) > 0) {

            $property = $this->getProperty($getMatches[0]);

            if (property_exists($this, $property)) {
                $meta = $this->_inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@read'])) {
                    throw  $this->_getExceptionForWriteOnly($property);
                }
                if (isset($this->$property)) {
                    return $this->$property;
                }
                return null;
            }
        }

        $setMatches = StringMethods::match($name, '^set(' . self::REGEX_METHOD_NAME . '+)$');

        if (sizeof($setMatches) > 0) {

            $property = $this->getProperty($setMatches[0]);

            if (property_exists($this, $property)) {
                $meta = $this->_inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@read'])) {
                    throw  $this->_getExceptionForReadonly($property);
                }
                $this->$property = $arguments[0];
                return $this;
            }
        }

        throw $this->_getExceptionForImplementation($name);
    }

    public function __get(string $name)
    {
        $function = 'get' . ucfirst($name);
        return $this->$function();
    }

    public function __set(string $name, mixed $value)
    {
        $function = 'set' . ucfirst($name);
        return $this->$function($value);
    }

    private function getProperty(string $matches): string
    {
        $normalized = lcfirst($matches);
        return '_' . $normalized;
    }

    private function _getExceptionForWriteOnly(string $property): ReadOnlyException
    {
        return new ReadOnlyException($property . ' jest tylko do odczytu.');
    }

    private function _getExceptionForReadonly(string $property): WriteOnlyException
    {
        return new WriteOnlyException($property . ' jest tylko do zapisu.');
    }

    private function _getExceptionForProperty(): ProperyException
    {
        return new ProperyException('Nie prawidłowa własność.');
    }

    private function _getExceptionForImplementation(string $name): ArgumentException
    {
        return new ArgumentException('Metoda: ' . $name . ' nie jest zaimplementowana.');
    }

}