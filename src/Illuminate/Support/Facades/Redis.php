<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Redis\RedisInterface
 */
class Redis extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'redis'; }

}
