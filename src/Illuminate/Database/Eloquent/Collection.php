<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Arr;
use UnexpectedValueException;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Fill the collection with new items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function fill($items = [])
    {
        $items = is_array($items) ? $items : $this->getArrayableItems($items);
        $this->items = Arr::check($items, [$this, 'checkItem']);

        return $this;
    }

    /**
     * Find a model in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        return Arr::first($this->items, function ($itemKey, Model $model) use ($key) {
            return $model->getKey() == $key;

        }, $default);
    }

    /**
     * Load a set of relationships onto the collection.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function load($relations)
    {
        if (count($this->items) > 0) {
            if (is_string($relations)) {
                $relations = func_get_args();
            }

            $query = $this->first()->newQuery()->with($relations);

            $this->items = $query->eagerLoadRelations($this->items);
        }

        return $this;
    }

    /**
     * Add an item to the collection.
     *
     * @param  Model  $item
     * @return $this
     */
    public function add(Model $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Determine if a key exists in the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $value = null)
    {
        if (func_num_args() == 2) {
            return parent::contains($key, $value);
        }

        if ($this->useAsCallable($key)) {
            return parent::contains($key);
        }

        $key = $key instanceof Model ? $key->getKey() : $key;

        return parent::contains(function ($k, Model $m) use ($key) {
            return $m->getKey() == $key;
        });
    }

    /**
     * Get the array of primary keys.
     *
     * @return array
     */
    public function modelKeys()
    {
        return array_map(function (Model $m) { return $m->getKey(); }, $this->items);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function merge($items)
    {
        $dictionary = $this->getDictionary();

        Arr::check($items, [$this, 'checkItem']);

        foreach ($items as $item) {
            $dictionary[$item->getKey()] = $item;
        }

        return new static(array_values($dictionary));
    }

    /**
     * Diff the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function diff($items)
    {
        $diff = new static;

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (! isset($dictionary[$item->getKey()])) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function intersect($items)
    {
        $intersect = new static;

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[$item->getKey()])) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Return only unique items from the collection.
     *
     * @param  string|callable|null  $key
     * @return static
     */
    public function unique($key = null)
    {
        if (! is_null($key)) {
            return parent::unique($key);
        }

        return new static(array_values($this->getDictionary()));
    }

    /**
     * Returns only the models from the collection with the specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        $dictionary = Arr::only($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Returns all models in the collection except the models with specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        $dictionary = Arr::except($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Make the given, typically hidden, attributes visible across the entire collection.
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function withHidden($attributes)
    {
        $this->each(function (Model $model) use ($attributes) {
            $model->withHidden($attributes);
        });

        return $this;
    }

    /**
     * Get a dictionary keyed by primary keys.
     *
     * @param  \ArrayAccess|array  $items
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : Arr::check($items, [$this, 'checkItem']);

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$value->getKey()] = $value;
        }

        return $dictionary;
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toBase()
    {
        return new BaseCollection($this->items);
    }

    /**
     * Check if an item can be put into collection.
     *
     * @param  mixed  $item
     * @return bool
     */
    public function checkItem($item)
    {
        return $item instanceof Model;
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  Model  $value
     * @return void
     *
     * @throws \UnexpectedValueException
     */
    public function offsetSet($key, $value)
    {
        if (! $this->checkItem($value)) {
            throw new UnexpectedValueException('Eloquent collection can be filled with models only');
        }

        parent::offsetSet($key, $value);
    }
}
