<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Register the application's event listeners.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        foreach ($this->listen as $event => $listeners) {
            if (Str::contains($event, '::')) {
                list($model, $eloquent_event) = explode('::', $event);
                $event = 'eloquent.'.$eloquent_event.': '.$model;
            }

            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            $events->subscribe($subscriber);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        $listens = [];

        foreach ($this->listen as $event => $listeners) {
            if (Str::contains($event, '::')) {
                list($model) = explode('::', $event);
                $event = $model;
            }

            if (array_key_exists($event, $listens)) {
                $listens[$event] = array_merge($listens[$event], $listeners);
            } else {
                $listens[$event] = $listeners;
            }
        }

        return $listens;
    }
}
