<?php

namespace Illuminate\Foundation\Benchmark;

use Illuminate\Support\Arr;

class Benchmark
{
    /**
     * The benchmark renderer.
     *
     * @var \Illuminate\Contracts\Foundation\BenchmarkRenderer
     */
    protected $renderer;

    /**
     * The number of repeats.
     *
     * @var int
     */
    protected $repeat = 10;

    /**
     * Creates a new "pending" Benchmark instance.
     *
     * @param  \Illuminate\Contracts\Foundation\BenchmarkRenderer  $renderer
     * @return void
     */
    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * The number of times a benchmark should be repeated.
     *
     * @param  int  $times
     * @return $this
     */
    public function repeat($times)
    {
        $this->repeat = $times;

        return $this;
    }

    /**
     * Measure the execution time of a callback.
     *
     * @param  iterable<string|int, \Closure(): mixed>|\Closure(): mixed  $callbacks
     * @return never
     */
    public function measure($callbacks)
    {
        $results = $this->getClosures($callbacks)->map(function ($callback, $key) {
            $average = (float) collect(range(1, $this->repeat))->map(function () use ($callback) {
                gc_collect_cycles();

                $start = microtime(true);

                $callback();

                return microtime(true) - $start;
            })->average();

            return new Result($callback, $key, $average);
        })->values();

        $this->renderer->render($results, $this->repeat);
    }

    /**
     * Get a collection of closures from the given callback(s).
     *
     * @param  iterable<string|int, \Closure(): mixed>|\Closure(): mixed  $callbacks
     * @return \Illuminate\Support\Collection<string|int, \Closure(): mixed>
     *
     * @throws \InvalidArgumentException
     */
    protected function getClosures($callbacks)
    {
        return collect(Arr::wrap($callbacks))
            ->whenEmpty(fn () => throw new \InvalidArgumentException('You must provide at least one callback.'));
    }
}
