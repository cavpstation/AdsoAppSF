<?php

namespace Illuminate\Log\Context;

use __PHP_Incomplete_Class;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Log\Context\Events\Dehydrating;
use Illuminate\Log\Context\Events\Hydrated;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Throwable;

class Repository
{
    use Macroable, SerializesModels;

    /**
     * The event dispatcher.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * The contextual data.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * The hidden contextual data.
     *
     * @var array<string, mixed>
     */
    protected $hidden = [];

    /**
     * Callback to handle unserialize exceptions.
     *
     * @var callable|null
     */
    protected $handleUnserializeExceptionUsing;

    /**
     * Create a new Context instance.
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Set the given key's value.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function add($key, $value = null)
    {
        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Set the given key's value as hidden.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHidden($key, $value = null)
    {
        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $key => $value) {
            $this->hidden[$key] = $value;
        }

        return $this;
    }

    /**
     * Forget the given key's context.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forget($key)
    {
        foreach ((array) $key as $k) {
            unset($this->data[$k]);
        }

        return $this;
    }

    /**
     * Forget the given key's hidden context.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forgetHidden($key)
    {
        foreach ((array) $key as $k) {
            unset($this->hidden[$k]);
        }

        return $this;
    }

    /**
     * Set the given key's value if it does not yet exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addIf($key, $value)
    {
        if (! $this->has($key)) {
            $this->add($key, $value);
        }

        return $this;
    }

    /**
     * Set the given key's value as hidden if it does not yet exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHiddenIf($key, $value)
    {
        if (! $this->hasHidden($key)) {
            $this->addHidden($key, $value);
        }

        return $this;
    }

    /**
     * Retrieve the given key's value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Retrieve the given key's hidden value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getHidden($key)
    {
        return $this->hidden[$key] ?? null;
    }

    /**
     * Retrieve only the values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only($keys)
    {
        return array_reduce($keys, function ($carry, $key) {
            if (! $this->has($key)) {
                return $carry;
            }

            return [
                ...$carry,
                ...[$key => $this->get($key)],
            ];
        }, []);
    }

    /**
     * Retrieve only the hidden values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function onlyHidden($keys)
    {
        return array_reduce($keys, function ($carry, $key) {
            if (! $this->hasHidden($key)) {
                return $carry;
            }

            return [
                ...$carry,
                ...[$key => $this->getHidden($key)],
            ];
        }, []);
    }

    /**
     * Push the given values onto the key's stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function push($key, ...$values)
    {
        if (! $this->isStackable($key)) {
            throw new RuntimeException("Unable to push value onto context stack for key [{$key}].");
        }

        $this->data[$key] = [
            ...$this->data[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Push the given values onto the key's hidden stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function pushHidden($key, ...$values)
    {
        if (! $this->isHiddenStackable($key)) {
            throw new RuntimeException("Unable to push value onto hidden context stack for key [{$key}].");
        }

        $this->hidden[$key] = [
            ...$this->hidden[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Determine if the given key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Determine if the given key exists as hidden.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasHidden($key)
    {
        return array_key_exists($key, $this->hidden);
    }

    /**
     * Execute the given callback when context is about to be dehydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function dehydrating($callback)
    {
        $this->events->listen(fn (Dehydrating $event) => $callback($event->context));

        return $this;
    }

    /**
     * Execute the given callback when context has been hydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function hydrated($callback)
    {
        $this->events->listen(fn (Hydrated $event) => $callback($event->context));

        return $this;
    }

    /**
     * Retrieve all the values.
     *
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Retrieve all the hidden values.
     *
     * @return array<string, mixed>
     */
    public function allHidden()
    {
        return $this->hidden;
    }

    /**
     * Determine if a given key can be used as a stack.
     */
    public function isStackable($key)
    {
        if (! $this->has($key)) {
            return true;
        }

        if (is_array($this->data[$key]) && array_is_list($this->data[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a given key can be used as a hidden stack.
     */
    public function isHiddenStackable($key)
    {
        if (! $this->hasHidden($key)) {
            return true;
        }

        if (is_array($this->hidden[$key]) && array_is_list($this->hidden[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the repository is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->all() === [] && $this->allHidden() === [];
    }

    /**
     * Handle unserialize exceptions using the given callback.
     *
     * @param  callable  $callback
     * @return static
     */
    public function handleUnserializeExceptionUsing($callback)
    {
        $this->handleUnserializeExceptionUsing = $callback;

        return $this;
    }

    /**
     * Flush all state.
     *
     * @return $this
     */
    public function flush()
    {
        $this->data = [];

        $this->hidden = [];

        return $this;
    }

    /**
     * Dehydrate the context data.
     *
     * @internal
     *
     * @return ?array
     */
    public function dehydrate()
    {
        $instance = (new static($this->events))
            ->add($this->all())
            ->addHidden($this->allHidden());

        $instance->events->dispatch(new Dehydrating($instance));

        $serialize = fn ($value) => serialize($instance->getSerializedPropertyValue($value, withRelations: false));

        return $instance->isEmpty() ? null : [
            'data' => array_map($serialize, $instance->all()),
            'hidden' => array_map($serialize, $instance->allHidden()),
        ];
    }

    /**
     * Hydrate the context instance.
     *
     * @internal
     *
     * @param  ?array  $context
     * @return $this
     */
    public function hydrate($context)
    {
        $unserialize = function ($value, $key, $type) {
            try {
                return tap($this->getRestoredPropertyValue(unserialize($value)), function ($value) {
                    if ($value instanceof __PHP_Incomplete_Class) {
                        throw new RuntimeException('Value is incomplete class: '.json_encode($value));
                    }
                });
            } catch (Throwable $e) {
                return $this->handleUnserializeException($e, $key, $value, $type);
            }
        };

        [$data, $hidden] = [
            collect($context['data'] ?? [])->map(fn ($value, $key) => $unserialize($value, $key, false))->all(),
            collect($context['hidden'] ?? [])->map(fn ($value, $key) => $unserialize($value, $key, true))->all(),
        ];

        $this->events->dispatch(new Hydrated(
            $this->flush()->add($data)->addHidden($hidden)
        ));

        return $this;
    }

    /**
     * Handle exceptions while unserializing.
     *
     * @internal
     *
     * @param  \Throwable  $e
     * @param  string  $key
     * @param  string  $value
     * @param  bool  $hidden
     * @return mixed
     */
    protected function handleUnserializeException($e, $key, $value, $hidden)
    {
        if ($this->handleUnserializeExceptionUsing !== null) {
            return ($this->handleUnserializeExceptionUsing)($e, $key, $value, $hidden);
        }

        if ($e instanceof ModelNotFoundException) {
            report($e);

            return null;
        }

        throw $e;
    }
}
