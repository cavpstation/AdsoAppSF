<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * Handle database transactions on the specified connections.
     *
     * @return void
     */
    public function setUpDatabaseTransactions()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);

                $connection->rollBack();
                $connection->disconnect();
            }
        });
    }

    /**
     * Handle database transactions on the specified connections.
     *
     * @deprecated in favor of setUpDatabaseTransactions()
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $this->setUpDatabaseTransactions();
    }

    /**
     * The database connections that should have transactions.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }
}
