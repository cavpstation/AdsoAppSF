<?php

namespace Illuminate\Bus;

use Illuminate\Support\Arr;

trait Queueable
{
    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue;

    /**
     * The name of the connection the chain should be sent to.
     *
     * @var string|null
     */
    public $chainConnection;

    /**
     * The name of the queue the chain should be sent to.
     *
     * @var string|null
     */
    public $chainQueue;

    /**
     * The number of seconds before the job should be made available.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $delay;

    /**
     * The middleware the job should be dispatched through.
     */
    public $middleware = [];

    /**
     * The jobs that should run if this job is successful.
     *
     * @var array
     */
    public $chained = [];

    /**
     * The batch id.
     *
     * @var string
     */
    public $batchId;

    /**
     * Set the desired connection for the job.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired connection for the chain.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function allOnConnection($connection)
    {
        $this->chainConnection = $connection;
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the chain.
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function allOnQueue($queue)
    {
        $this->chainQueue = $queue;
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Specify the middleware the job should be dispatched through.
     *
     * @param  array|object  $middleware
     * @return $this
     */
    public function through($middleware)
    {
        $this->middleware = Arr::wrap($middleware);

        return $this;
    }

    /**
     * Set the batch ID.
     *
     * @param  string  $id
     * @return $this
     */
    public function batchId($id)
    {
        $this->batchId = $id;

        return $this;
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return $this
     */
    public function chain($chain)
    {
        $this->chained = collect($chain)->map(function ($job) {
            return serialize($job);
        })->all();

        return $this;
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function dispatchNextJobInChain()
    {
        if (! empty($this->chained)) {
            dispatch(tap(unserialize(array_shift($this->chained)), function ($next) {
                $next->chained = $this->chained;

                $next->onConnection($next->connection ?: $this->chainConnection);
                $next->onQueue($next->queue ?: $this->chainQueue);

                $next->chainConnection = $this->chainConnection;
                $next->chainQueue = $this->chainQueue;
            }));
        }
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function invokeBatchCallback()
    {
        if (! $this->batchId ||
            ! $batch = app('cache')->get('batch_'.$this->batchId)) {
            return;
        }

        if (app('cache')->decrement('batch_'.$this->batchId.'_counter') == 0 &&
            $callback = json_decode($batch, true)['success']) {

            app()->call(unserialize($callback)->getClosure());
        }
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return void
     */
    public function invokeBatchFailureCallback()
    {
        if (! $this->batchId ||
            ! $batch = app('cache')->get('batch_'.$this->batchId)) {
            return;
        }

        app('cache')->forget('batch_'.$this->batchId.'_counter');

        if ($callback = json_decode($batch, true)['failure']) {
            app('cache')->forget('batch_'.$this->batchId);

            app()->call(unserialize($callback)->getClosure());
        }
    }

    /**
     * Dispatch the next job on the chain.
     *
     * @return boolean
     */
    public function batchIsRunning()
    {
        return app('cache')->has('batch_'.$this->batchId);
    }
}
