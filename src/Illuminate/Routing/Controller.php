<?php namespace Illuminate\Routing;

use Closure;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Controller {

	/**
	 * The "before" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $beforeFilters = [];

	/**
	 * The "after" filters registered on the controller.
	 *
	 * @var array
	 */
	protected $afterFilters = [];

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * The route filterer implementation.
	 *
	 * @var \Illuminate\Routing\RouteFiltererInterface
	 */
	protected static $filterer;

	/**
	 * Register a "before" filter on the controller.
	 *
	 * @param  \Closure|string  $filter
	 * @param  array  $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = [])
	{
		$this->beforeFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Register an "after" filter on the controller.
	 *
	 * @param  \Closure|string  $filter
	 * @param  array  $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = [])
	{
		$this->afterFilters[] = $this->parseFilter($filter, $options);
	}

	/**
	 * Parse the given filter and options.
	 *
	 * @param  \Closure|string  $filter
	 * @param  array  $options
	 * @return array
	 */
	protected function parseFilter($filter, array $options)
	{
		$parameters = [];

		$original = $filter;

		if ($filter instanceof Closure)
		{
			$filter = $this->registerClosureFilter($filter);
		}
		elseif ($this->isInstanceFilter($filter))
		{
			$filter = $this->registerInstanceFilter($filter);
		}
		else
		{
			list($filter, $parameters) = Route::parseFilter($filter);
		}

		return compact('original', 'filter', 'parameters', 'options');
	}

	/**
	 * Register an anonymous controller filter Closure.
	 *
	 * @param  \Closure  $filter
	 * @return string
	 */
	protected function registerClosureFilter(Closure $filter)
	{
		$this->getFilterer()->filter($name = spl_object_hash($filter), $filter);

		return $name;
	}

	/**
	 * Register a controller instance method as a filter.
	 *
	 * @param  string  $filter
	 * @return string
	 */
	protected function registerInstanceFilter($filter)
	{
		$this->getFilterer()->filter($filter, [$this, substr($filter, 1)]);

		return $filter;
	}

	/**
	 * Determine if a filter is a local method on the controller.
	 *
	 * @param  mixed  $filter
	 * @return boolean
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function isInstanceFilter($filter)
	{
		if (is_string($filter) && starts_with($filter, '@'))
		{
			if (method_exists($this, substr($filter, 1))) return true;

			throw new \InvalidArgumentException("Filter method [$filter] does not exist.");
		}

		return false;
	}

	/**
	 * Remove the given before filter.
	 *
	 * @param  string  $filter
	 * @return void
	 */
	public function forgetBeforeFilter($filter)
	{
		$this->beforeFilters = $this->removeFilter($filter, $this->getBeforeFilters());
	}

	/**
	 * Remove the given after filter.
	 *
	 * @param  string  $filter
	 * @return void
	 */
	public function forgetAfterFilter($filter)
	{
		$this->afterFilters = $this->removeFilter($filter, $this->getAfterFilters());
	}

	/**
	 * Remove the given controller filter from the provided filter array.
	 *
	 * @param  string  $removing
	 * @param  array  $current
	 * @return array
	 */
	protected function removeFilter($removing, $current)
	{
		return array_filter($current, function($filter) use ($removing)
		{
			return $filter['original'] != $removing;
		});
	}

	/**
	 * Get the registered "before" filters.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->beforeFilters;
	}

	/**
	 * Get the registered "after" filters.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->afterFilters;
	}

	/**
	 * Get the route filterer implementation.
	 *
	 * @return \Illuminate\Routing\RouteFiltererInterface
	 */
	public static function getFilterer()
	{
		return static::$filterer;
	}

	/**
	 * Set the route filterer implementation.
	 *
	 * @param  \Illuminate\Routing\RouteFiltererInterface  $filterer
	 * @return void
	 */
	public static function setFilterer(RouteFiltererInterface $filterer)
	{
		static::$filterer = $filterer;
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function callAction($method, $parameters)
	{
		return call_user_func_array([$this, $method], $parameters);
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function missingMethod($parameters = [])
	{
		throw new NotFoundHttpException("Controller method not found.");
	}

	/**
	 * Set the container instance on the controller.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		throw new \BadMethodCallException("Method [$method] does not exist.");
	}

}
