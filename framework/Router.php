<?php

declare(strict_types=1);

namespace Framework;

use Framework\Exceptions\ActionException;
use Framework\Exceptions\ControllerException;
use Framework\Exceptions\ImplementationException;
use mysql_xdevapi\Exception;
use ReflectionException;


class Router extends Base
{
    /**
     * @readwrite
     */
    protected string $_url;
    /**
     * @readwrite
     */
    protected $_extension;

    /**
     * @read
     */
    protected string $_controller;

    /**
     * @read
     */
    protected string $_action;
    protected array $_routes = [];

    /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }

    public function setUrl(string $url): void
    {
        $this->_url = $url;
    }

    public function addRoute($route): Router
    {
        $this->_routes[] = $route;
        return $this;
    }

    public function removeRoute($route): Router
    {
        foreach ($this->_routes as $i => $stored) {
            if ($stored = $route) {
                unset($this->_routes[$i]);
            }
        }

        return $this;
    }

    public function getRoutes(): array
    {
        $list = [];
        foreach ($this->_routes as $route) {
            $list[$route->pattern] = get_class($route);
        }
        return $list;
    }

    /**
     * @throws ActionException
     * @throws ControllerException
     * @throws ReflectionException
     */
    public function dispatch(): void
    {
        $url = $this->_url;
        $parameters = [];
        $controller = 'index';
        $action = '';

        foreach ($this->_routes as $route) {
            $matches = $route->matches($url);
            if ($matches) {
                $controller = $route->controller;
                $action = $route->action;
                $parameters = $route->parameters;
                $this->_pass($controller, $action, $parameters);
                return;
            }
        }
        $parts = explode('/', trim($url, '/'));
        if (sizeof($parts) > 0) {
            $controller = $parts[0];
            if (sizeof($parts) >= 2) {
                $action = $parts[1];
                $parameters = array_splice($parts, 2);
            }
        }
        $this->_pass($controller, $action, $parameters);
    }

    /**
     * @throws ControllerException
     * @throws ReflectionException
     * @throws ActionException
     */
    protected function _pass(string $controller, string $action, array $parameters = []): void
    {
        $name = ucfirst($controller);
        $this->_controller = $controller;
        $this->_action = $action;

        try {
            $instance = new $name(['parameters' => $parameters]);
            Registry::get('controller', $instance);

        } catch (Exception $e) {
            throw new ControllerException('Nie znaleziono kontrolera: ' . $name . '.');
        }

        if (!method_exists($instance, $action)) {
            $instance->willRenderLayoutView = false;
            $instance->willAcrionLayoutView = false;
            throw new ActionException('Nie znaleziono akcji: ' . $action . '.');
        }

        $inspector = new Inspector($instance);
        $methodMeta = $inspector->getMethodMeta($action);

        if (!empty($methodMeta['@protected']) || !empty($methodMeta['@private'])) {
            throw new ActionException('Nie znaleziono akcji.');
        }

        $hooks = function ($meta, $type) use ($inspector, $instance) {

            if (isset($meta[$type])) {
                $run = [];
                foreach ($meta[$type] as $method) {

                    $hookMeta = $inspector->getMethodMeta($method);

                    if (in_array($method, $run) && !empty($hookMeta['@once'])) {
                        continue;
                    }

                    $instance->$method();
                    $run[] = $method;
                }
            }
        };

        $hooks($methodMeta, '@before');
        call_user_func([$instance, $action], is_array($parameters ? $parameters : []));
        $hooks($methodMeta, '@after');

        Registry::erase('controller');
    }
}