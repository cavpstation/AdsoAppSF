<?php namespace Illuminate\Exception;

use Exception;
use Whoops\Run;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WhoopsDisplayer implements ExceptionDisplayerInterface {

	/**
	 * The Whoops run instance.
	 *
	 * @var \Whoops\Run
	 */
	protected $whoops;

	/**
	 * Indicates if the application is in a console environment.
	 *
	 * @var bool
	 */
	protected $runningInConsole;

	/**
	 * Create a new Whoops exception displayer.
	 *
	 * @param  \Whoops\Run  $whoops
	 * @param  bool  $runningInConsole
	 * @return void
	 */
	public function __construct(Run $whoops, $runningInConsole)
	{
		$this->whoops = $whoops;
		$this->runningInConsole = $runningInConsole;
	}

	/**
	 * Display the given exception to the user.
	 *
	 * @param  \Exception  $exception
	 */
	public function display(Exception $exception)
	{
		$status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

		return new Response($this->whoops->handleException($exception), $status);
	}

}
