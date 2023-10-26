<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\ArrayMethods;
use Framework\Base;
use Framework\Exceptions\{ActionException, ImplementationException, SqlException};

class Query extends Base
{
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
    protected ?int $_limit = null;
    /**
     * @read
     */
    protected ?int $_offset = null;
    /**
     * @read
     */
    protected ?string $_order = null;
    /**
     * @read
     */
    protected ?string $_direction = null;
    /**
     * @read
     */
    protected array $_join = [];
    /**
     * @read
     */
    protected array $_where = [];

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
        $result = $this->_connector->execute(trim($sql));

        if ($result === false) {
            throw new SqlException();
        }

        return $this->_connector->affectedRows;
    }

    /**
     * @throws ActionException
     */
    public function from(string $from, array $fields = ['*']): Query
    {
        if (empty($from)) {
            throw new ActionException('Nieprawidłowy argument');
        }
        $this->_from = $from;

        if ($fields) {
            $this->_fields[$from] = $fields;
        }
        return $this;
    }

    /**
     * @throws ActionException
     */
    public function join(string $join, string $on, array $fields = []): Query
    {
        if (empty($join)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        if (empty($on)) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $this->_fields += [$join => $fields];
        $this->_join[] = "JOIN {$join} ON {$on}";

        return $this;
    }

    /**
     * @throws ActionException
     */
    public function limit(int $limit, int $page = 1): static
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
    public function order(string $order, string $direction = 'asc'): static
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

        if (sizeof($arguments) < 1) {
            throw new ActionException('Nieprawidłowy argument');
        }

        $arguments[0] = preg_replace('#\?#', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
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

    protected function _quote($value)
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
            return "({$buffer})";
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (int)$value;
        }

        return $this->_connector->escape($value);
    }

    protected function _buildSelect(): string
    {
        $fields = [];
        $where = $order = $limit = $join = '';
        $template = 'SELECT %s FROM %s %s %s %s %s';

        foreach ($this->_fields as $table => $_fields) {
            foreach ($_fields as $field => $alias) {
                if (is_string($field)) {
                    $fields[] = "{$field} AS `{$alias}`";
                } else {
                    $fields[] = $alias;
                }
            }
        }

        $fields = join(', ', $fields);

        $_join = $this->_join;
        if (!empty($_join)) {
            $join = join(' ', $_join);
        }

        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = join(' AND ', $_where);
            $where = "WHERE {$joined}";
        }

        $_order = $this->_order;
        if (!empty($_order)) {
            $_direction = $this->_direction;
            $order = "ORDER BY {$_order} {$_direction}";
        }

        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;

            if ($_offset) {
                $limit = "LIMIT {$_limit}, {$_offset}";
            } else {
                $limit = "LIMIT {$_limit}";
            }
        }

        return sprintf($template, $fields, $this->_from, $join, $where, $order, $limit);
    }

    protected function _buildInsert($data): string
    {
        $fields = [];
        $values = [];
        $template = "INSERT INTO `%s` (`%s`) VALUES (%s)";

        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $this->_quote($value);
        }

        $fields = join('`, `', $fields);
        $values = join(', ', $values);

        return sprintf($template, $this->_from, $fields, $values);
    }

    protected function _buildUpdate($data): string
    {
        $parts = [];
        $where = $limit = '';
        $template = 'UPDATE %s SET %s %s %s';

        foreach ($data as $field => $value) {
            $parts[] = "{$field} = " . $this->_quote($value);
        }

        $parts = join(', ', $parts);

        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = join(', ', $_where);
            $where = "WHERE {$joined}";
        }

        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        return sprintf($template, $this->_from, $parts, $where, $limit);
    }

    protected function _buildDelete(): string
    {
        $where = $limit = '';
        $template = 'DELETE FROM %s %s %s';

        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = join(', ', $_where);
            $where = "WHERE {$joined}";
        }

        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        return sprintf($template, $this->_from, $where, $limit);
    }

    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . ' nie jest zaimplementowana');
    }

}