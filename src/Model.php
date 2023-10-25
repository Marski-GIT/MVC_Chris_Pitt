<?php

declare(strict_types=1);

namespace Framework;

use Framework\Exceptions\ImplementationException;
use Framework\Exceptions\PrimaryException;
use Framework\Exceptions\TypeException;
use MongoDB\Driver\Exception\ConnectionException;
use ReflectionException;

class Model extends Base
{
    /**
     * @readwrite
     */
    protected string $_table = '';
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
    protected string $_primary;
    private array $primaryColumn;

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
            $previous = $this->_connector
                ->query()
                ->from($this->_table)
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
            return $this->_connector
                ->query()
                ->from($this->_table)
                ->where("{$name} = ?", $this->$raw)
                ->delete();
        }
    }

    public function deleteAll(array $where = [])
    {
        $instance = new static();
        $query = $instance->_connector
            ->query()
            ->from($instance->_table);

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

        $query = $this->_connector
            ->query()
            ->from($this->_table);

        if (!empty($this->$raw)) {
            $query->whwrw("{$name} = ?", $this->$raw);
        }

        $data = [];

        foreach ($this->_columns as $key => $column) {

            if (!$column['head']) {
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
     * @throws ReflectionException
     * @throws TypeException
     * @throws PrimaryException
     */
    public function getColumns(): array
    {
        if (empty($_columns)) {
            $primaries = 0;
            $columns = [];
            $class = get_class($this);
            $types = $this->_types;

            $inspector = new Inspector($this);
            $properties = $inspector->getClassProperties();

            $first = function ($array, string $key) {
                if (!empty($array[$key]) && sizeof($array[$key]) === 1) {
                    return $array[$key][0];
                }
                return null;
            };

            foreach ($properties as $property) {
                $propertyMeta = $inspector->getPropertyMeta($property);

                if (!empty($propertyMeta['@column'])) {

                    $name = preg_replace('#^#', '', $property);
                    $primary = !empty($propertyMeta['@primary']);
                    $type = $first($propertyMeta, '@type');
                    $length = $first($propertyMeta, '@length');
                    $index = !empty($propertyMeta['@index']);
                    $readwrite = !empty($propertyMeta['@readwrite']);
                    $read = !empty($propertyMeta['@read']);
                    $write = !empty($propertyMeta['@write']);

                    $validate = !empty($propertyMeta['@validate']) ? $propertyMeta['@validate'] : false;
                    $label = $first($propertyMeta, '$@label');

                    if (!in_array($type, $types)) {
                        throw new TypeException('Typ ' . $type . ' jest nieprawidłowy.');
                    }

                    if ($primary) {
                        $primaries++;
                    }

                    $columns[$name] = [
                        'raw'       => $property,
                        'name'      => $name,
                        'primary'   => $primary,
                        'type'      => $type,
                        'length'    => $length,
                        'index'     => $index,
                        'readwrite' => $readwrite,
                        'read'      => $read,
                        'write'     => $write,
                        'validate'  => $validate,
                        'label'     => $label
                    ];
                }

            }

            if (!$primaries != 1) {
                throw new PrimaryException('klasa ' . $class . ' musi mieć dokładnie jedną kolumnę.');
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
            foreach ($this->_columns as $column) {

                if ($column['primary']) {
                    $primary = $column;
                    break;
                }
            }
            $this->_primary = $primary;
        }
        return $this->_primary;
    }

    public function getConnector()
    {
        if (empty($this->_connector)) {
            $database = Registry::get('database');

            if (!$database) {
                throw new ConnectionException('Brak dostępnego konektora');
            }

            $this->_connector = $database->initialize();
        }

        return $this->_connector;
    }

    public static function first(array $where = [], array $fields = ['*'], $order = null, $direction = null)
    {
        $model = new static();
        return $model->_first($where, $fields, $order, $direction);
    }

    public static function count(array $where = [])
    {
        $model = new static();
        return $model->_count($where);
    }

    public static function all(array $where = [], array $fields = ['*'], $order = null, $direction = null, $limit = null, $page = null): array
    {
        $model = new static();
        return $model->_all($where, $fields, $order, $direction, $limit, $page);
    }

    protected function _first(array $where = [], array $fields = ['*'], $order = null, $direction = null)
    {
        $query = $this->_connector
            ->query()
            ->from($this->_table, $fields);

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
        $query = $this->_connector
            ->query()
            ->from($this->_table, $fields);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($order != null) {
            $query->order($order, $direction);
        }

        if ($limit != null) {
            $query->limit($limit, $page);
        }

        $rows = array();
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
        $query = $this->_connector
            ->query()
            ->from($this->_table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->count();
    }

    /**
     * @param string $name
     * @return ImplementationException
     */
    protected function _getExceptionForImplementation(string $name): ImplementationException
    {
        return new ImplementationException('Metoda ' . $name . 'nie jest zaimplementowana.');
    }
}