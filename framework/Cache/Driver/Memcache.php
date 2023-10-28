<?php

declare(strict_types=1);

namespace Framework\Cache\Driver;

use Exception;
use Framework\Exceptions\ServiceException;
use Memcached;

class Memcache extends Driver
{
    protected Memcached|null $_service = null;
    /**
     * @readwrite
     */
    protected string $_host = '127.0.0.1';
    /**
     * @readwrite
     */
    protected int $_port = 11211;
    /**
     * @readwrite
     */
    protected bool $_isConnected = false;

    /**
     * @return $this
     * @throws ServiceException
     */
    public function connect(): Memcache
    {
        try {
            $this->_service = new Memcached();
            $this->_service->addServer($this->_host, $this->_port);
            $this->_isConnected = true;

        } catch (Exception $e) {
            echo $e->getMessage();
            throw new ServiceException('Nie można połączyć się z usługą');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect(): Memcache
    {
        if ($this->_isValidService()) {
            $this->_service->quit();
            $this->_isConnected = false;
        }
        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws ServiceException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie można połączyć się z usługą');
        }

        $value = $this->_service->get($key);

        if ($value) {
            return $value;
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return $this
     * @throws ServiceException
     */
    public function set(string $key, mixed $value, int $expiration = 120): Memcache
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie można połączyć się z usługą');
        }
        $this->_service->set($key, $value, $expiration);
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     * @throws ServiceException
     */
    public function erase(string $key): Memcache
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie można połączyć się z usługą');
        }
        $this->_service->delete($key);
        return $this;
    }

    /**
     * @return bool
     */
    protected function _isValidService(): bool
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof MemCached;

        if ($this->_isConnected && $isInstance && !$isEmpty) {
            return true;
        }
        return false;
    }
}