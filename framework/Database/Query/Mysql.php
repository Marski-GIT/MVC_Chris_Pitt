<?php

declare(strict_types=1);

namespace Framework\Database\Query;

use Framework\Database\Query;
use Framework\Exceptions\SqlException;

class Mysql extends Query
{
    /**
     * @throws SqlException
     */
    public function all(): array
    {
        $sql = $this->_buildSelect();
        $result = $this->_connector->execute($sql);

        if ($result === false) {
            $error = $this->_connector->lastError;
            throw new SqlException('Wystąpił błąd w zapytaniu SQL: ' . $error);
        }

        $rows = [];

        for ($i = 0; $i < $result->num_rows; $i++) {
            $rows[] = $result->fetch_array(MYSQLI_ASSOC);
        }

        return $rows;
    }
}