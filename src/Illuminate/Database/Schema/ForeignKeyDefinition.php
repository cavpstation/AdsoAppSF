<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method ForeignKeyDefinition deferrable(bool $value = true) Set the foreign key as deferrable (PostgreSQL)
 * @method ForeignKeyDefinition initiallyImmediate(bool $value = true) Set the default time to check the constraint (PostgreSQL)
 * @method ForeignKeyDefinition on(string $table) Specify the referenced table
 * @method ForeignKeyDefinition onDelete(string $action) Add an ON DELETE action
 * @method ForeignKeyDefinition onUpdate(string $action) Add an ON UPDATE action
 * @method ForeignKeyDefinition references(string|array $columns) Specify the referenced column(s)
 */
class ForeignKeyDefinition extends Fluent
{
    /**
     * Indicate that updates should cascade.
     *
     * @return $this
     */
    public function cascadeOnUpdate()
    {
        return $this->onUpdate('cascade');
    }

    /**
     * Indicate that updates should be restricted.
     *
     * @return $this
     */
    public function restrictOnUpdate()
    {
        return $this->onUpdate('restrict');
    }

    /**
     * Indicate that updates should have "no action".
     *
     * @return $this
     */
    public function noActionOnUpdate()
    {
        return $this->onUpdate('no action');
    }

    /**
     * Indicate that deletes should cascade.
     *
     * @return $this
     */
    public function cascadeOnDelete()
    {
        return $this->onDelete('cascade');
    }

    /**
     * Indicate that deletes should be restricted.
     *
     * @return $this
     */
    public function restrictOnDelete()
    {
        return $this->onDelete('restrict');
    }

    /**
     * Indicate that deletes should set the foreign key value to null.
     *
     * @return $this
     */
    public function nullOnDelete()
    {
        return $this->onDelete('set null');
    }

    /**
     * Indicate that deletes should have "no action".
     *
     * @return $this
     */
    public function noActionOnDelete()
    {
        return $this->onDelete('no action');
    }

    /**
     * Indicate that updates and deletes should cascade.
     *
     * @return $this
     */
    public function cascadeAlways()
    {
        return $this->cascadeOnUpdate()->cascadeOnDelete();
    }

    /**
     * Indicate that updates and deletes should be restricted.
     *
     * @return $this
     */
    public function restrictAlways()
    {
        return $this->restrictOnUpdate()->restrictOnDelete();
    }

    /**
     * Indicate that updates and deletes should have "no action".
     *
     * @return $this
     */
    public function noActionAlways()
    {
        return $this->noActionOnUpdate()->noActionOnDelete();
    }

    /**
     * Indicate that updates should cascade and deletes should be null.
     *
     * @return $this
     */
    public function cascadeAndNull()
    {
        return $this->cascadeOnUpdate()->nullOnDelete();
    }

    /**
     * Indicate that updates should be restricted and deletes should be null.
     *
     * @return $this
     */
    public function restrictAndNull()
    {
        return $this->restrictOnUpdate()->nullOnDelete();
    }

    /**
     * Indicate that updates should have no-action and deletes should be null.
     *
     * @return $this
     */
    public function noActionAndNull()
    {
        return $this->noActionOnUpdate()->nullOnDelete();
    }
}
