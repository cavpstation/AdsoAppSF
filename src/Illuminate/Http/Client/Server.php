<?php

namespace Illuminate\Http\Client;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Http\Client\PendingRequest
 */
abstract class Server
{
    use ForwardsCalls;

    /**
     * The actions for this server.
     *
     * @var array{string:string}
     */
    protected $actions = [];

    /**
     * The base URL of this server.
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * The headers to include in the server requests.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The bearer token to use as authentication mechanism.
     *
     * @var string
     */
    protected $authToken;

    /**
     * The request to be sent, once built.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $request;

    /**
     * Create a new Server instance.
     *
     * @param  \Illuminate\Http\Client\Factory  $factory
     * @param  array  $urlParameters
     */
    public function __construct(protected $factory, protected $urlParameters = [])
    {
        //
    }

    /**
     * Customize the request created for this server.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return \Illuminate\Http\Client\PendingRequest|void
     */
    protected function build($request)
    {
        //
    }

    /**
     * Sets the query parameters for the URI.
     *
     * @param  array  $parameters
     * @return $this
     */
    public function parameters(array $parameters)
    {
        $this->urlParameters = $parameters;

        return $this;
    }

    /**
     * Builds a request based on the server configuration.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function buildRequest()
    {
        return $this->request ??= tap(
            $this->factory
                ->baseUrl($this->baseUrl)
                ->when($this->authToken)->withToken($this->authToken)
                ->when($this->headers)->withHeaders($this->headers)
                ->when($this->urlParameters)->withUrlParameters($this->urlParameters),
            $this->build(...)
        );
    }

    /**
     * Redirects the wait procedure to the underlying request promise.
     *
     * @param  bool  $unwrap
     * @return mixed
     */
    public function wait($unwrap = true)
    {
        return $this->getPromise()->wait($unwrap);
    }

    /**
     * Returns an action by its name, or null if it doesn't exist.
     *
     * @param  string  $name
     * @return string|null
     */
    public function findAction($name)
    {
        if (isset($this->actions[$name])) {
            return $this->actions[$name];
        }

        foreach ($this->actions as $key => $action) {
            if (Str::camel($key) === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Dynamically handle calls to the underlying request.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this|object
     *
     * @throws \Exception
     */
    public function __call(string $method, array $parameters)
    {
        $request = $this->buildRequest();

        if ($action = $this->findAction($method)) {
            [$verb, $path] = str_contains($action, ':') ? explode(':', $action, 2) : ['get', $action];

            return $request->{$verb}($path, ...$parameters);
        }

        return $this->forwardDecoratedCallTo($request, $method, $parameters);
    }

    /**
     * Creates a new server instance.
     *
     * @param  array  $parameters
     * @return static
     */
    public static function request($parameters = [])
    {
        $factory = class_exists('Illuminate\Container\Container')
            ? \Illuminate\Container\Container::getInstance()->make(Factory::class)
            : new Factory();

        return $factory->server(static::class, $parameters);
    }
}
