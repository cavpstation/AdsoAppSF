<?php namespace Illuminate\Routing; interface RouteFiltererInterface { public function filter($name, $callback); public function callRouteFilter($filter, $parameters, $route, $request, $response = null); }
