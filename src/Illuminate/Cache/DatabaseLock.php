<?php

namespace Illuminate\Cache;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

class DatabaseLock extends Lock
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The database table name.
     *
     * @var string
     */
    protected $table;

    /**
     * The prune probability odds.
     *
     * @var string
     */
    protected $lottery;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $table
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @param  array  $lottery
     * @return void
     */
    public function __construct(Connection $connection, $table, $name, $seconds, $owner = null, $lottery = [2, 100])
    {
        parent::__construct($name, $seconds, $owner);

        $this->connection = $connection;
        $this->table = $table;
        $this->lottery = $lottery;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $acquired = false;

        try {
            $this->connection->table($this->table)->insert([
                'id' => $this->name,
                'owner' => $this->owner,
                'expires_at' => $this->expiresAt(),
            ]);

            $acquired = true;
        } catch (QueryException $e) {
            $updated = $this->connection->table($this->table)
                ->where('id', $this->name)
                ->where(function ($query) {
                    return $query->where('owner', $this->owner)->orWhere('expires_at', '<=', time());
                })->update([
                    'owner' => $this->owner,
                    'expires_at' => $this->expiresAt(),
                ]);

            $acquired = $updated >= 1;
        }

        if (random_int(1, $this->lottery[1]) <= $this->lottery[0]) {
            $this->connection->table($this->table)->where('expires_at', '<=', time())->delete();
        }

        return $acquired;
    }

    /**
     * Get the UNIX timestamp indicating when the lock should expire.
     *
     * @return int
     */
    protected function expiresAt()
    {
        return $this->seconds > 0 ? time() + $this->seconds : now()->addDays(1)->getTimestamp();
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            $this->connection->table($this->table)
                        ->where('id', $this->name)
                        ->where('owner', $this->owner)
                        ->delete();

            return true;
        }

        return false;
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->connection->table($this->table)
                    ->where('id', $this->name)
                    ->delete();
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        return optional($this->connection->table($this->table)->where('id', $this->name)->first())->token;
    }
}
