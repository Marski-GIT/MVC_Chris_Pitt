<?php

declare(strict_types=1);

namespace Framework\Database\Connector;

use Framework\Database\Connector;
use Framework\Database\Query\Mysql as MysqlQuery;
use Framework\Exceptions\ServiceException;
use Framework\Exceptions\SqlException;
use mysqli;
use mysqli_driver;

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

            $driver = new mysqli_driver();
            $driver->report_mode = MYSQLI_REPORT_ALL;

            $this->_service = new mysqli(
                $this->_host,
                $this->_username,
                $this->_password,
                $this->_schema,
                $this->_port
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
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }

        return $this->_service->query(trim($sql));
    }

    /**
     * @throws ServiceException
     */
    public function escape(mixed $value): mixed
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }

        if (is_string($value)) {
            return $this->_service->real_escape_string($value);
        }

        return $value;
    }

    /**
     * @throws ServiceException
     */
    public function getLastInsertId(): int|string
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->insert_id;
    }

    /**
     * @throws ServiceException
     */
    public function getAffectedRows(): int|string
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->affected_rows;
    }

    /**
     * @throws ServiceException
     */
    public function getLastError(): string
    {
        if (!$this->_isValidService()) {
            throw new ServiceException('Nie połączono z usługą.');
        }
        return $this->_service->error;
    }

    /**
     * @throws ServiceException
     * @throws SqlException
     */
    public function sync($model): static
    {
        $lines = array();
        $indices = array();
        $columns = $model->columns;
        $template = "CREATE TABLE `%s` (\n%s,\n%s\n) ENGINE=%s DEFAULT CHARSET=%s;";

        foreach ($columns as $column) {
            $raw = $column['raw'];
            $name = $column['name'];
            $type = $column['type'];
            $length = $column['length'];

            if ($column['primary']) {
                $indices[] = "PRIMARY KEY (`{$name}`)";
            }
            if ($column['index']) {
                $indices[] = "KEY `{$name}` (`{$name}`)";
            }

            switch ($type) {
                case 'autonumber':
                    $lines[] = "`{$name}` int(11) NOT NULL AUTO_INCREMENT";
                    break;
                case 'text':
                    if ($length !== null && $length <= 255) {
                        $lines[] = "`{$name}` varchar({$length}) DEFAULT NULL";
                    } else {
                        $lines[] = "`{$name}` text";
                    }
                    break;
                case 'integer':
                    $lines[] = "`{$name}` int(11) DEFAULT NULL";
                    break;
                case 'decimal':
                    $lines[] = "`{$name}` float DEFAULT NULL";
                    break;
                case 'boolean':
                    $lines[] = "`{$name}` tinyint(4) DEFAULT NULL";
                    break;
                case 'datetime':
                    $lines[] = "`{$name}` datetime DEFAULT NULL";
                    break;
            }
        }

        $table = $model->table;
        $sql = sprintf(
            $template,
            $table,
            join(',\n', $lines),
            join(',\n', $indices),
            $this->_engine,
            $this->_charset
        );

        $result = $this->execute("DROP TABLE IF EXISTS {$table};");
        if ($result === false) {
            $error = $this->getLastError();
            throw new  SqlException('Wystąpił błąd w zapytaniu: ' . $error);
        }

        $result = $this->execute($sql);
        if ($result === false) {
            $error = $this->getLastError();
            throw new  SqlException('Wystąpił błąd w zapytaniu: ' . $error);
        }

        return $this;
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