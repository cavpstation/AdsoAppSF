<?php

namespace Illuminate\Queue\Failed;

use DateTimeInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;

class FileFailedJobProvider implements FailedJobProviderInterface, PrunableFailedJobProvider
{
    /**
     * The path at which the failed job file should be stored.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new database failed job provider.
     *
     * @param  string  $path
     * @return void
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Throwable  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $id = json_decode($payload, true)['uuid'];

        $jobs = $this->read();

        $jobs[] = [
            'id' => $id,
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => (string) mb_convert_encoding($exception, 'UTF-8'),
            'failed_at' => Date::now()->getTimestamp(),
        ];

        $this->write($jobs);
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return $this->read();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        return collect($this->read())
            ->first(fn ($job) => $job->id === $id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        $this->write(collect($this->read())->reject(fn ($job) => $job->id === $id)->values()->all());
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @param  int|null  $hours
     * @return void
     */
    public function flush($hours = null)
    {
        $this->prune(Date::now()->subHours($hours ?: 0));
    }

    /**
     * Prune all of the entries older than the given date.
     *
     * @param  \DateTimeInterface  $before
     * @return int
     */
    public function prune(DateTimeInterface $before)
    {
        $jobs = $this->read();

        $deleted = 0;

        $prunedJobs = collect($jobs)->reject(function ($job) use (&$deleted) {
            return $job->failed_at <= $before->getTimestamp();
        })->values()->all();

        $this->write($prunedJobs);

        return count($jobs) - count($prunedJobs);
    }

    public function read()
    {
        if (! file_exists($this->path.'/failed-jobs.json')) {
            return [];
        }

        $content = file_get_contents($this->path.'/failed-jobs.json');

        if (empty(trim($content))) {
            return [];
        }

        $content = json_decode($content);

        return is_array($content) ? $content : [];
    }

    public function write(array $jobs)
    {
        file_put_contents(
            $this->path.'/failed-jobs.json',
            json_encode($jobs, JSON_PRETTY_PRINT)
        );
    }
}
