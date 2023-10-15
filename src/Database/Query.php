<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\ArrayMethods;
use Framework\Base;
use Framework\Exceptions\{ActionException, ImplementationException, SqlException};

class Query extends Base
{
    const  SELECT_TEMPLATE = 'SELECT %s FROM %s %s %s %s %s';
    const INSERT_TEMPLATE = "INSERT INTO '%s' ('%s') VALUES (%s)";
    const UPDATE_TEMPLATE = "UPDATE %s SET %s %s %s";
    const DELETE_TEMPLATE = "DELETE FROM %s %s %s";

    /**
     * @readwrite
     */
    protected $_connector;
    /**
     * @read
     */
    protected string $_from;
    /**
     * @read
     */
    protected array $_fields = [];
    /**
     * @read
     */
    protected int $_limit;
    /**
     * @read
     */
    protected int $_offset;
    /**
     * @read
     */
    protected string $_order;
    /**
     * @read
     */
    protected string $_direction;
    /**
     * @read
     */
    protected array $_join = [];
    /**
     * @read
     */
    protected array $_where = [];

    /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }

    public function all(): array
    {
        return [];
    }

    /**
     * @throws SqlException
     */
    public function save($data)
    {
        $isInsert = sizeof($this->_where) == 0;

        if ($isInsert) {
            $sql = $this->_buildInsert($data);
        } else {
            $sql = $this->_buildUpdate($data);
        }

        $result = $this->_connector->execute($sql);

        if ($result === false) {
            throw new SqlException();
        }

        if ($isInsert) {
            return $this->_connector->lastInsertId;
        }

        return 0;
    }

    /**
     * @throws SqlException
     */
    public function delete()
    {
        $sql = $this->_buildDelete();
        $result = $this->_connector->execute($sql);

        if ($result === false) {
            throw new SqlException();
        }

        return $this->_connector->affectedRows;
    }

    /**
     * @throws ActionException
     */
    public function from(string $from, array $fields = []): Query
    {
        if (empty($from)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $this->_from = $from;

        if (!empty($fields)) {
            $this->_fields[$from] = $fields;
        }

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function join(string $join, string $on, $fields = []): Query
    {
        if (empty($join)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        if (empty($on)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $this->_fields += [$join => $fields];
        $this->_join[] = "JOIN {$join} OM {$on}";

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function limit(int $limit, int $page = 1): Query
    {
        if (empty($limit)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $this->_limit = $limit;
        $this->_offset = $limit * ($page - 1);

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function order(string $order, string $direction = 'ASC'): Query
    {
        if (empty($order)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $this->_order = $order;
        $this->_direction = $direction;

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function where(): Query
    {
        $arguments = func_get_args();

        if (!func_num_args()) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $arguments[0] = preg_replace('#\?#', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parametr) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }

        $this->_where[] = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function first()
    {
        $limit = $this->_limit;
        $offset = $this->_offset;

        $this->limit(1);

        $all = $this->all();
        $first = ArrayMethods::first($all);

        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $first;
    }

    /**
     * @throws ActionException
     */
    public function count()
    {
        $limit = $this->_limit;
        $offset = $this->_offset;
        $fields = $this->_fields;

        $this->_fields = [$this->_from => ['COUNT(1)' => 'rows']];

        $this->limit(1);
        $row = $this->first();

        $this->_fields = $fields;

        if ($fields) {
            $this->_fields = $fields;
        }
        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $row['rows'];
    }

    protected function _buildSelect(): string
    {
        $fields = [];
        $where = $order = $limit = $join = '';


        foreach ($this->_fields as $table => $_fields) {

            foreach ($_fields as $field => $alias) {
                if (is_string($field)) {
                    $fields[] = "{$field} AS {$alias}";
                } else {
                    $fields[] = $alias;
                }
            }
        }

        $fields = join(', ', $fields);

        if (!empty($this->_join)) {
            $join = join(' ', $this->_join);
        }

        if (!empty($this->_where)) {
            $joined = join(' AND ', $this->_where);
            $where = "WHERE {$joined}";
        }

        if (!empty($this->_order)) {
            $_direction = $this->_direction;
            $order = "ORDER BY {$this->_order} {$_direction}";
        }

        if (!empty($this->_limit)) {

            if (empty($this->_offset)) {
                $limit = "LIMIT {$this->_limit}, {$this->_offset}";
            } else {
                $limit = "LIMIT {$this->_limit}";
            }
        }

        return sprintf(self::SELECT_TEMPLATE, $fields, $this->_from, $join, $where, $order, $limit);
    }

    protected function _buildInsert(array $data): string
    {
        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $field[] = $field;
            $values[] = $this->_quote($value);
        }

        $fields = join("', '", $fields);
        $values = join(', ', $values);

        return sprintf(self::INSERT_TEMPLATE, $this->_from, $fields, $values);
    }

    protected function _buildUpdate(array $data): string
    {
        $parts = [];
        $where = $limit = '';

        foreach ($data as $field => $value) {
            $parts[] = "{$value} = " . $this->_quote($value);
        }

        $parts = join(', ', $parts);

        if (!empty($this->where)) {
            $joined = join(', ', $this->where);
            $where = "WHERE {$joined}";
        }

        if (!empty($this->limit)) {
            $limit = "LIMIT {$this->limit} {$this->_offset}";
        }

        return sprintf(self::UPDATE_TEMPLATE, $this->_from, $parts, $where, $limit);
    }

    protected function _buildDelete(): string
    {
        $where = $limit = '';

        if (!empty($this->where)) {
            $joined = join(', ', $this->where);
            $where = "WHERE {$joined}";
        }

        if (!empty($this->limit)) {
            $limit = "LIMIT {$this->limit} {$this->_offset}";
        }

        return sprintf(self::DELETE_TEMPLATE, $this->_from, $where, $limit);
    }

    protected function _quote($value): string|int
    {
        if (is_string($value)) {
            $escaped = $this->_connector->escape($value);
            return "'{$escaped}'";
        }
        if (is_array($value)) {
            $buffer = [];

            foreach ($value as $i) {
                $buffer[] = $this->_quote($i);
            }

            $buffer = join(', ', $buffer);
            return "'{$buffer}'";
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (int)$value;
        }

        return $this->_connector->escape($value);
    }
}