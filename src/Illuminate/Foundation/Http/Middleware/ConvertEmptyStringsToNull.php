<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\Concerns\HasSkipCallbacks;

class ConvertEmptyStringsToNull extends TransformsRequest
{
    use HasSkipCallbacks;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->shouldSkipDueToCallback($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Transform the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        return $value === '' ? null : $value;
    }
}
