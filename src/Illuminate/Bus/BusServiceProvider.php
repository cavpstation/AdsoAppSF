<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Dispatcher::class, static function ($app) {
            return new Dispatcher($app, static function ($connection = null) use ($app) {
                return $app[QueueFactoryContract::class]->connection($connection);
            });
        });

        $this->app->alias(
            Dispatcher::class, DispatcherContract::class
        );

        $this->app->alias(
            Dispatcher::class, QueueingDispatcherContract::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Dispatcher::class,
            DispatcherContract::class,
            QueueingDispatcherContract::class,
        ];
    }
}
