<?php

namespace Illuminate\Database\Query\Processors;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class SqlServerProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->insert($sql, $values);

        if ($connection->getConfig('odbc') === true) {
            $id = $this->processInsertGetIdForOdbc($connection);
        } else {
            $id = $connection->getPdo()->lastInsertId();
        }

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process an "insert get ID" query for ODBC.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return int
     *
     * @throws \Exception
     */
    protected function processInsertGetIdForOdbc(Connection $connection)
    {
        $result = $connection->selectFromWriteConnection(
            'SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS int) AS insertid'
        );

        if (! $result) {
            throw new Exception('Unable to retrieve lastInsertID for ODBC.');
        }

        $row = $result[0];

        return is_object($row) ? $row->insertid : $row['insertid'];
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            $type = match ($typeName = $result->type_name) {
                'binary', 'varbinary', 'char', 'varchar', 'nchar', 'nvarchar' => $result->length == -1 ? $typeName.'(max)' : $typeName."($result->length)",
                'decimal', 'numeric' => $typeName."($result->precision,$result->places)",
                'float', 'datetime2', 'datetimeoffset', 'time' => $typeName."($result->precision)",
                default => $typeName,
            };

            return [
                'name' => $result->name,
                'type_name' => $result->type_name,
                'type' => $type,
            ];
        }, $results);
    }
}
