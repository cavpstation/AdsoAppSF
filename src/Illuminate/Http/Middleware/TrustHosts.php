<?php

namespace Illuminate\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class TrustHosts
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The trusted hosts that have been configured to always be trusted.
     *
     * @var array<int, string>|null
     */
    protected static $alwaysTrust;

    /**
     * Whether to trust subdomains of the application url.
     *
     * @var bool|null
     */
    protected static $subdomains;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the host patterns that should be trusted.
     *
     * @return array
     */
    public function hosts()
    {
        if (is_array(static::$alwaysTrust)) {
            $hosts = static::$alwaysTrust;

            if (static::$subdomains) {
                $hosts[] = $this->allSubdomainsOfApplicationUrl();
            }

            return $hosts;
        }

        return [$this->allSubdomainsOfApplicationUrl()];
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $next)
    {
        if ($this->shouldSpecifyTrustedHosts()) {
            Request::setTrustedHosts(array_filter($this->hosts()));
        }

        return $next($request);
    }

    /**
     * Specify the hosts that should always be trusted.
     *
     * @param  array<int, string>  $hosts
     * @param  bool  $subdomains
     * @return void
     */
    public static function at(array $hosts, bool $subdomains = true)
    {
        static::$alwaysTrust = $hosts;
        static::$subdomains = $subdomains;
    }

    /**
     * Determine if the application should specify trusted hosts.
     *
     * @return bool
     */
    protected function shouldSpecifyTrustedHosts()
    {
        return ! $this->app->environment('local') &&
               ! $this->app->runningUnitTests();
    }

    /**
     * Get a regular expression matching the application URL and all of its subdomains.
     *
     * @return string|null
     */
    protected function allSubdomainsOfApplicationUrl()
    {
        if ($host = parse_url($this->app['config']->get('app.url'), PHP_URL_HOST)) {
            return '^(.+\.)?'.preg_quote($host).'$';
        }
    }

    /**
     * Flush the state of the middleware.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$alwaysTrust = null;
        static::$subdomains = null;
    }
}
