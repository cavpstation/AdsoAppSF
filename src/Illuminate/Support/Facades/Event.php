<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'events'; }

}