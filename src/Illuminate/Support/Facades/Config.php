<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Config\Repository
 */
final class Config extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'config'; }

}