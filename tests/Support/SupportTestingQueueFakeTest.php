<?php

namespace Illuminate\Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Testing\Fakes\QueueFake;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class QueueFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new QueueFake(new Application);
        $this->job = new JobStub;
    }

    public function testAssertPushed()
    {
        try {
            $this->fake->assertPushed(JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.'));
        }

        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class);
    }

    public function testAssertNotPushed()
    {
        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        try {
            $this->fake->assertNotPushed(JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.'));
        }
    }

    public function testAssertPushedOn()
    {
        $this->fake->push($this->job, '', 'foo');

        try {
            $this->fake->assertPushedOn('bar', JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.'));
        }

        $this->fake->assertPushedOn('foo', JobStub::class);
    }

    public function testAssertPushedTimes()
    {
        $this->fake->push($this->job);
        $this->fake->push($this->job);

        try {
            $this->fake->assertPushed(JobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertNothingPushed()
    {
        $this->fake->assertNothingPushed();

        $this->fake->push($this->job);

        try {
            $this->fake->assertNothingPushed();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Jobs were pushed unexpectedly.'));
        }
    }

    public function testAssertPushedUsingBulk()
    {
        $this->fake->assertNothingPushed();

        $queue = 'my-test-queue';
        $this->fake->bulk([
            $this->job,
            new JobStub(),
        ], null, $queue);

        $this->fake->assertPushedOn($queue, JobStub::class);
        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertPushedWithChain()
    {
        $jobWithChain = new JobWithChainStub([
            new JobStub,
            new JobWithParameterStub(0)
        ]);

        $jobWithParameterAndChain = new JobWithParameterAndChainStub('first', [
            new JobStub,
        ]);
        $anotherJobWithParameterAndChain = new JobWithParameterAndChainStub('second', [
            new JobWithParameterStub(1)
        ]);

        $this->fake->push($jobWithChain);
        $this->fake->push($jobWithParameterAndChain);
        $this->fake->push($anotherJobWithParameterAndChain);

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            new JobStub,
            new JobWithParameterStub(0)
        ]);

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            JobStub::class,
            JobWithParameterStub::class
        ]);

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected chain can not be empty'));
        }

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, [
                new JobStub
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected chain was not pushed'));
        }

        $this->fake->assertPushedWithChain(JobWithParameterAndChainStub::class, [
            new JobStub
        ], function ($job) {
            return $job->parameter == 'first';
        });

        try {
            $this->fake->assertPushedWithChain(NotPushedJobStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\NotPushedJobStub] job was not pushed'));
        }
    }
}

class JobStub
{
    public function handle()
    {
        //
    }
}

class JobWithParameterStub
{
    public $number;

    function __construct($number)
    {
        $this->number = $number;
    }

    public function handle()
    {
        //
    }
}

class JobWithChainStub
{
    use Queueable;

    function __construct($chain)
    {
        $this->chain($chain);
    }

    public function handle()
    {
        //
    }
}

class NotPushedJobStub
{
    public function handle()
    {
        //
    }
}

class JobWithParameterAndChainStub
{
    use Queueable;

    public $parameter;

    function __construct($parameter, $chain)
    {
        $this->parameter = $parameter;
        $this->chain($chain);
    }

    public function handle()
    {
        //
    }
}