<?php namespace Illuminate\Events; use Illuminate\Support\ServiceProvider; class EventServiceProvider extends ServiceProvider { public function register() { $this->app['events'] = $this->app->share(function($app) { return new Dispatcher($app); }); } }
