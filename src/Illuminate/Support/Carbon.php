<?php

namespace Illuminate\Support;

use JsonSerializable;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Traits\Macroable;

class Carbon extends BaseCarbon implements JsonSerializable
{
    use Macroable;

    /**
     * The custom Carbon JSON serializer.
     *
     * @var callable|null
     */
    protected static $serializer;

    /**
     * Prepare the object for JSON serialization.
     *
     * @return array|string
     */
    public function jsonSerialize()
    {
        if (static::$serializer) {
            return call_user_func(static::$serializer, $this);
        }

        $carbon = $this;

        return call_user_func(function () use ($carbon) {
            return get_object_vars($carbon);
        });
    }

    /**
     * JSON serialize all Carbon instances using the given callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function serializeUsing($callback)
    {
        static::$serializer = $callback;
    }

    /**
     * When a Carbon instance is exported using var_export() and reinstated using __set_state(),
     * make sure it is reconstructed as a Carbon object instead of a DateTime object.
     *
     * @param  array  $properties
     * @return static
     */
    public static function __set_state(array $properties)
    {
        return static::instance(
            parent::__set_state($properties)
        );
    }
}
