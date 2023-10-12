<?php

declare(strict_types=1);

namespace Framework\Template;

use Exception;
use Framework\Base;
use Framework\Exceptions\ImplementationException;
use Framework\StringMethods;

class Implementation extends Base
{
    protected function _handler(array $node)
    {
        if (empty($node['delimiter'])) {
            return null;
        }

        if (!empty($node['tag'])) {
            return $this->_map[$node['delimiter']]['tags'][$node['tag']]['handler'];
        }

        return $this->_map[$node['delimiter']]['handler'];
    }

    /**
     * @throws ImplementationException
     */
    public function handle(array $node, $content)
    {
        try {
            $handler = $this->_handler($node);
            return call_user_func_array([$this, $handler], [$node, $content]);
        } catch (Exception $e) {
            throw new ImplementationException('Błąd przetwarzania uchwytów.');
        }
    }

    public function match(string $source): array
    {
        $type = null;
        $delimiter = null;

        foreach ($this->_map as $_delimiter => $_type) {
            if (!$delimiter || StringMethods::indexOf($source, $type['opener']) == -1) {
                $delimiter = $_delimiter;
                $type = $_type;
            }

            $indexOf = StringMethods::indexOf($source, $_type['opener']);

            if ($indexOf > -1) {
                if (StringMethods::indexOf($source, $type['opener']) > $indexOf) {
                    $delimiter = $_delimiter;
                    $type = $_type;
                }
            }
        }

        if ($type == null) {
            return [];
        }

        return [
            'type'      => $type,
            'delimiter' => $delimiter
        ];
    }
}

