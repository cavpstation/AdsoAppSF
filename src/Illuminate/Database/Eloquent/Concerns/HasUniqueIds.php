<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HasUniqueIds
{
    /**
     * Indicates if the model uses unique ids.
     *
     * @var bool
     */
    public $uniqueIds = false;

    /**
     * Determine if the model uses unique ids.
     *
     * @return bool
     */
    public function usesUniqueIds()
    {
        return $this->uniqueIds;
    }

    /**
     * Generate a unique keys for model.
     *
     * @return void
     */
    public function setUniqueIds()
    {
        foreach ($this->uniqueIds() as $column) {
            if (empty($this->{$column})) {
                $this->{$column} = $this->newUniqueId();
            }
        }
    }

    /**
     * Generate a new key for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return null;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return [];
    }
}
