<?php

namespace Illuminate\Testing;

use Illuminate\Support\Facades\ParallelTesting;
use ParaTest\Runners\PHPUnit\Options;
use ParaTest\Runners\PHPUnit\RunnerInterface;
use ParaTest\Runners\PHPUnit\WrapperRunner;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ParallelRunner implements RunnerInterface
{
    /**
     * The application resolver callback.
     *
     * @var \Closure|null
     */
    protected static $applicationResolver;

    /**
     * The original test runner options.
     *
     * @var \ParaTest\Runners\PHPUnit\Options
     */
    protected $options;

    /**
     * The output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The original test runner.
     *
     * @var \ParaTest\Runners\PHPUnit\RunnerInterface
     */
    protected $runner;

    /**
     * Creates a new test runner instance.
     *
     * @param  \ParaTest\Runners\PHPUnit\Options  $options
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(Options $options, OutputInterface $output)
    {
        $this->options = $options;

        if ($output instanceof ConsoleOutput) {
            $output = new ParallelConsoleOutput($output);
        }

        $this->runner = new WrapperRunner($options, $output);
    }

    /**
     * Set the application resolver callback.
     *
     * @param  \Closure|null  $resolver
     * @return void
     */
    public static function resolveApplicationUsing($resolver)
    {
        static::$applicationResolver = $resolver;
    }

    /**
     * Runs the test suite.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $this->runner->run();
        } finally {
            $this->forEachProcess(function ($app) {
                ParallelTesting::callBeforeProcessDestroyedCallbacks();
            });
        }
    }

    /**
     * Returns the highest exit code encountered throughout the course of test execution.
     *
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->runner->getExitCode();
    }

    /**
     * Apply the given callback for each process.
     *
     * @param  callable $callback
     * @return void
     */
    protected function forEachProcess($callback)
    {
        collect(range(1, $this->options->processes()))->each(function ($token) use ($callback) {
            tap($this->createApplication(), function ($app) use ($callback, $token) {
                ParallelTesting::resolveTokenUsing(function () use ($token) {
                    return $token;
                });

                $callback($app);
            })->flush();
        });
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function createApplication()
    {
        $applicationResolver = static::$applicationResolver ?: function () {
            $applicationCreator = new class {
                use \Tests\CreatesApplication;
            };

            return $applicationCreator->createApplication();
        };

        return call_user_func($applicationResolver);
    }
}
