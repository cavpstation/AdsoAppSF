<?php namespace Illuminate\Cache; interface StoreInterface { public function get($key); public function put($key, $value, $minutes); public function increment($key, $value = 1); public function decrement($key, $value = 1); public function forever($key, $value); public function forget($key); public function flush(); public function getPrefix(); }
