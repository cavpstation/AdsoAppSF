<?php

namespace Illuminate\Database\Schema;

class SqlServerBuilder extends Builder
{
    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($name, $this->connection)
        );
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name)
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabaseIfExists($name)
        );
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->connection->statement($this->grammar->compileDropAllForeignKeys());

        $this->connection->statement($this->grammar->compileDropAllTables());
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $this->connection->statement($this->grammar->compileDropAllViews());
    }

    /**
     * Drop all procedures from the database.
     *
     * @return void
     */
    public function dropAllProcedures()
    {
        $procedures = [];

        foreach ($this->getAllProcedures() as $row) {
            $row = (array) $row;

            $procedures[] = $row['routine_name'];
        }

        if (empty($procedures)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllProcedures($procedures)
        );
    }

    /**
     * Drop all tables from the database.
     *
     * @return array
     */
    public function getAllTables()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTables()
        );
    }

    /**
     * Get all of the view names for the database.
     *
     * @return array
     */
    public function getAllViews()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllViews()
        );
    }

    /**
     * Get all of the procedures names for the database.
     *
     * @return array
     */
    public function getAllProcedures()
    {
        return $this->connection->select(
            $this->grammar->compileGetAllProcedures($this->getSchema())
        );
    }

    /**
     * Get database schema.
     *
     * @return string
     */
    protected function getSchema()
    {
        return $this->connection->getConfig('schema') ?: 'dbo';
    }
}
