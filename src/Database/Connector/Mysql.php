<?php

declare(strict_types=1);

namespace Framework\Database\Connector;

use Framework\Database\Connector;
use Framework\Database\Query\Mysql as MysqlQuery;
use Framework\Exceptions\ServiceException;
use mysqli;

class Mysql extends Connector
{

    protected mysqli|null $_service = null;
    /**
     * @readwrite
     */
    protected string $_host;
    /**
     * @readwrite
     */
    protected string $_username;
    /**
     * @readwrite
     */
    protected string $_password;
    /**
     * @readwrite
     */
    protected string $_schema;
    /**
     * @readwrite
     */
    protected int $_port = 3306;
    /**
     * @readwrite
     */
    protected string $_charset = 'utf8';
    /**
     * @readwrite
     */
    protected string $_engine = 'InnoDb';
    /**
     * @readwrite
     */
    protected bool $_isConnected = false;

    /**
     * @throws ServiceException
     */
    public function connect(): Mysql
    {
        if (!$this->_isValidService()) {
            $this->_service = new Mysqli(
                $this->_host,
                $this->_username,
                $this->_password,
                $this->_schema,
                $this->_port,
            );

            if ($this->_service->connect_error) {
                throw new  ServiceException('Nie można nawiązać połączenia z MySQL.');
            }
            $this->_isConnected = true;
        }

        return $this;
    }

    public function disconnect(): Mysql
    {
        if ($this->_isValidService()) {
            $this->_isConnected = false;
            $this->_service->close();
        }

        return $this;
    }

    public function query(): MysqlQuery
    {
        return new MysqlQuery(['connector' => $this]);
    }

    /**
     * @throws ServiceException
     */
    public function execute(string $sql)
    {
        if ($this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->query($sql);
    }

    /**
     * @throws ServiceException
     */
    public function escape(string $value): string
    {
        if ($this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->real_escape_string($value);
    }

    /**
     * @throws ServiceException
     */
    public function getLastInsertId()
    {
        if ($this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->insert_id;
    }

    /**
     * @throws ServiceException
     */
    public function getAffectedRows()
    {
        if ($this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->affected_rows;
    }

    /**
     * @throws ServiceException
     */
    public function getLastError(): string
    {
        if ($this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->error;
    }

    protected function _isValidService(): bool
    {
        $isEmpty = is_null($this->_service);
        $isInstance = $this->_service instanceof MySQLi;

        if ($this->_isConnected && $isInstance && !$isEmpty) {
            return true;
        };

        return false;
    }
}