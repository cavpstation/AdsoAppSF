<?php

namespace Illuminate\Cache\Events;

class CacheGetMany extends CacheEvent
{
    /**
     * The keys that are retrieved.
     *
     * @var array
     */
    public $keys;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $keys
     * @param  array  $tags
     * @return void
     */
    public function __construct($storeName, $keys, array $tags = [])
    {
        parent::__construct($storeName, $keys[0] ?? '', $tags);

        $this->keys = $keys;
    }
}
