<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Routing\Route addRoute(array|string $methods, string $uri, array|string|callable|null $action)
 * @method static \Illuminate\Routing\Router aliasMiddleware(string $name, string $class)
 * @method static \Illuminate\Routing\Route any(string $uri, array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\PendingResourceRegistration apiResource(string $name, string $controller, array $options = [])
 * @method static void apiResources(array $resources, array $options = [])
 * @method static void bind(string $key, string|callable $binder)
 * @method static \Illuminate\Routing\Route|null current()
 * @method static string|null currentRouteAction()
 * @method static string|null currentRouteName()
 * @method static bool currentRouteNamed(mixed ...$patterns)
 * @method static bool currentRouteUses(string $action)
 * @method static \Illuminate\Routing\Route delete(string $uri, array|string|callable|null $action = null)
 * @method static \Symfony\Component\HttpFoundation\Response dispatch(\Illuminate\Http\Request $request)
 * @method static \Symfony\Component\HttpFoundation\Response dispatchToRoute(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Routing\Route fallback(array|string|callable|null $action)
 * @method static void flushMacros()
 * @method static \Illuminate\Routing\Router flushMiddlewareGroups()
 * @method static array gatherRouteMiddleware(\Illuminate\Routing\Route $route)
 * @method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null)
 * @method static \Closure|null getBindingCallback(string $key)
 * @method static \Illuminate\Http\Request getCurrentRequest()
 * @method static \Illuminate\Routing\Route|null getCurrentRoute()
 * @method static array getGroupStack()
 * @method static string getLastGroupPrefix()
 * @method static array getMiddleware()
 * @method static array getMiddlewareGroups()
 * @method static array getPatterns()
 * @method static \Illuminate\Routing\RouteCollectionInterface getRoutes()
 * @method static void group(array $attributes, \Closure|array|string $routes)
 * @method static bool has(string|array $name)
 * @method static bool hasGroupStack()
 * @method static bool hasMacro(string $name)
 * @method static bool hasMiddlewareGroup(string $name)
 * @method static mixed input(string $key, string|null $default = null)
 * @method static bool is(mixed ...$patterns)
 * @method static void macro(string $name, object|callable $macro)
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \Illuminate\Routing\Route match(array|string $methods, string $uri, array|string|callable|null $action = null)
 * @method static void matched(string|callable $callback)
 * @method static array mergeWithLastGroup(array $new, bool $prependExistingPrefix = true)
 * @method static \Illuminate\Routing\Router middlewareGroup(string $name, array $middleware)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static void model(string $key, string $class, \Closure|null $callback = null)
 * @method static \Illuminate\Routing\Route newRoute(array|string $methods, string $uri, mixed $action)
 * @method static \Illuminate\Routing\Route options(string $uri, array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route patch(string $uri, array|string|callable|null $action = null)
 * @method static void pattern(string $key, string $pattern)
 * @method static void patterns(array $patterns)
 * @method static \Illuminate\Routing\Route permanentRedirect(string $uri, string $destination)
 * @method static \Illuminate\Routing\Route post(string $uri, array|string|callable|null $action = null)
 * @method static \Symfony\Component\HttpFoundation\Response prepareResponse(\Symfony\Component\HttpFoundation\Request $request, mixed $response)
 * @method static \Illuminate\Routing\Router prependMiddlewareToGroup(string $group, string $middleware)
 * @method static \Illuminate\Routing\Router pushMiddlewareToGroup(string $group, string $middleware)
 * @method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null)
 * @method static \Illuminate\Routing\Route redirect(string $uri, string $destination, int $status = 302)
 * @method static array resolveMiddleware(array $middleware, array $excluded = [])
 * @method static \Illuminate\Routing\PendingResourceRegistration resource(string $name, string $controller, array $options = [])
 * @method static void resourceParameters(array $parameters = [])
 * @method static array|null resourceVerbs(array $verbs = [])
 * @method static void resources(array $resources, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\Response respondWithRoute(string $name)
 * @method static void setCompiledRoutes(array $routes)
 * @method static \Illuminate\Routing\Router setContainer(\Illuminate\Container\Container $container)
 * @method static void setRoutes(\Illuminate\Routing\RouteCollection $routes)
 * @method static void singularResourceParameters(bool $singular = true)
 * @method static \Illuminate\Routing\Route substituteBindings(\Illuminate\Routing\Route $route)
 * @method static void substituteImplicitBindings(\Illuminate\Routing\Route $route)
 * @method static \Symfony\Component\HttpFoundation\Response toResponse(\Symfony\Component\HttpFoundation\Request $request, mixed $response)
 * @method static array uniqueMiddleware(array $middleware)
 * @method static bool uses(array ...$patterns)
 * @method static \Illuminate\Routing\Route view(string $uri, string $view, array $data = [], int|array $status = 200, array $headers = [])
 *
 * @see \Illuminate\Routing\Router
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
