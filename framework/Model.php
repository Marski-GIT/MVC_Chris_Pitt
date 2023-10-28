<?php

declare(strict_types=1);

namespace Framework;

use Framework\Exceptions\ControllerException;
use Framework\Exceptions\ImplementationException;
use Framework\Exceptions\PrimaryException;
use Framework\Exceptions\TypeException;

use ReflectionException;

class Model extends Base
{
    /**
     * @readwrite
     */
    protected string $_table;

    /**
     * @readwrite
     */
    protected object $_connector;

    /**
     * @read
     */
    protected array $_types = [
        'autonumber',
        'text',
        'integer',
        'decimal',
        'boolean',
        'datetime'
    ];

    protected array $_columns;
    protected array $_primary;

    public function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . ' nie jest zaimplementowana');
    }

    /**
     * @throws PrimaryException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->load();
    }

    /**
     * @throws PrimaryException
     */
    public function load(): void
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $previous = $this->connector
                ->query()
                ->from($this->table)
                ->where("{$name} = ?", $this->$raw)
                ->first();

            if ($previous == null) {
                throw new PrimaryException('Nieprawidłowy klucz główny');
            }

            foreach ($previous as $key => $value) {
                $prop = "_{$key}";
                if (!empty($previous->$key) && !isset($this->$prop)) {
                    $this->$key = $previous->$key;
                }
            }
        }
    }

    public function delete()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            return $this->connector
                ->query()
                ->from($this->table)
                ->where("{$name} = ?", $this->$raw)
                ->delete();
        }
    }

    public static function deleteAll($where = [])
    {
        $instance = new static();

        $query = $instance->connector
            ->query()
            ->from($instance->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->delete();
    }

    public function save()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        $query = $this->connector
            ->query()
            ->from($this->table);

        if (!empty($this->$raw)) {
            $query->where("{$name} = ?", $this->$raw);
        }

        $data = [];
        foreach ($this->columns as $key => $column) {
            if (!$column['read']) {
                $prop = $column['raw'];
                $data[$key] = $this->$prop;
                continue;
            }

            if ($column != $this->primaryColumn && $column) {
                $method = 'get' . ucfirst($key);
                $data[$key] = $this->$method();
                continue;
            }
        }

        $result = $query->save($data);

        if ($result > 0) {
            $this->$raw = $result;
        }

        return $result;
    }

    public function getTable(): string
    {
        if (empty($this->_table)) {
            $this->_table = strtolower(StringMethods::singular(get_class($this)));
        }

        return $this->_table;
    }

    /**
     * @throws ControllerException
     */
    public function getConnector()
    {
        if (empty($this->_connector)) {
            $database = Registry::get('database');

            if (!$database) {
                throw new ControllerException('Brak dostępnego konektora');
            }

            $this->_connector = $database->initialize();
        }

        return $this->_connector;
    }

    /**
     * @throws ReflectionException
     * @throws PrimaryException
     * @throws TypeException
     */
    public function getColumns(): array
    {
        if (empty($_columns)) {
            $primaries = 0;
            $columns = [];
            $class = get_class($this);
            $types = $this->types;

            $inspector = new Inspector($this);
            $properties = $inspector->getClassProperties();

            $first = function ($array, $key) {
                if (!empty($array[$key]) && sizeof($array[$key]) == 1) {
                    return $array[$key][0];
                }
                return null;
            };

            foreach ($properties as $property) {
                $propertyMeta = $inspector->getPropertyMeta($property);

                if (!empty($propertyMeta['@column'])) {
                    $name = preg_replace('#^_#', '', $property);
                    $primary = !empty($propertyMeta['@primary']);
                    $type = $first($propertyMeta, '@type');
                    $length = $first($propertyMeta, '@length');
                    $index = !empty($propertyMeta['@index']);
                    $readwrite = !empty($propertyMeta['@readwrite']);
                    $read = !empty($propertyMeta['@read']) || $readwrite;
                    $write = !empty($propertyMeta['@write']) || $readwrite;

                    $validate = !empty($propertyMeta['@validate']) ? $propertyMeta['@validate'] : false;
                    $label = $first($propertyMeta, '@label');

                    if (!in_array($type, $types)) {
                        throw new TypeException('Typ ' . $type . ' jest nieprawidłowy.');
                    }

                    if ($primary) {
                        $primaries++;
                    }

                    $columns[$name] = [
                        'raw'      => $property,
                        'name'     => $name,
                        'primary'  => $primary,
                        'type'     => $type,
                        'length'   => $length,
                        'index'    => $index,
                        'read'     => $read,
                        'write'    => $write,
                        'validate' => $validate,
                        'label'    => $label
                    ];
                }
            }

            if ($primaries !== 1) {
                throw new PrimaryException('Klasa ' . $class . ' musi mieć dokładnie jedną kolumnę @primary');
            }

            $this->_columns = $columns;
        }

        return $this->_columns;
    }

    public function getColumn($name)
    {
        if (!empty($this->_columns[$name])) {
            return $this->_columns[$name];
        }
        return null;
    }

    public function getPrimaryColumn()
    {
        if (!isset($this->_primary)) {
            $primary = null;

            foreach ($this->columns as $column) {
                if ($column['primary']) {
                    $primary = $column;
                    break;
                }
            }

            $this->_primary = $primary;
        }

        return $this->_primary;
    }

    public static function count(array $where = [])
    {
        $model = new static();
        return $model->_count($where);
    }

    public static function first(array $where = [], $fields = ['*'], $order = null, $direction = null)
    {
        $model = new static();
        return $model->_first($where, $fields, $order, $direction);
    }


    public static function all(array $where = [], array $fields = ['*'], $order = null, $direction = null, $limit = null, $page = null): array
    {
        $model = new static();
        return $model->_all($where, $fields, $order, $direction, $limit, $page);
    }

    protected function _first(array $where = [], $fields = ['*'], $order = null, $direction = null)
    {
        $query = $this->connector
            ->query()
            ->from($this->table, $fields);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($order != null) {
            $query->order($order, $direction);
        }

        $first = $query->first();
        $class = get_class($this);

        if ($first) {
            return new $class(
                $query->first()
            );
        }

        return null;
    }

    protected function _all(array $where = [], array $fields = ['*'], $order = null, $direction = null, $limit = null, $page = null): array
    {
        $query = $this->connector
            ->query()
            ->from($this->table, $fields);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($order != null) {
            $query->order($order, $direction);
        }

        if ($limit != null) {
            $query->limit($limit, $page);
        }

        $rows = [];
        $class = get_class($this);

        foreach ($query->all() as $row) {
            $rows[] = new $class(
                $row
            );
        }

        return $rows;
    }

    protected function _count(array $where = [])
    {
        $query = $this->connector
            ->query()
            ->from($this->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->count();
    }
}