<?php

use Mockery as m;
use Illuminate\Queue\Listener;
use Symfony\Component\Process\Process;

class QueueListenerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRunProcessCallsProcess()
	{
		$process = m::mock(Process::class)->makePartial();
		$process->shouldReceive('run')->once();
		$listener = m::mock(Listener::class)->makePartial();
		$listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(false);

		$listener->runProcess($process, 1);
	}


	public function testListenerStopsWhenMemoryIsExceeded()
	{
		$process = m::mock(Process::class)->makePartial();
		$process->shouldReceive('run')->once();
		$listener = m::mock(Listener::class)->makePartial();
		$listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(true);
		$listener->shouldReceive('stop')->once();

		$listener->runProcess($process, 1);
	}


	public function testMakeProcessCorrectlyFormatsCommandLine()
	{
		$listener = new Listener(__DIR__);
		$process = $listener->makeProcess('connection', 'queue', 1, 2, 3);

		$this->assertInstanceOf(Process::class, $process);
		$this->assertEquals(__DIR__, $process->getWorkingDirectory());
		$this->assertEquals(3, $process->getTimeout());
		$this->assertEquals('"'.PHP_BINARY.'" artisan queue:work connection --queue="queue" --delay=1 --memory=2 --sleep=3 --tries=0', $process->getCommandLine());
	}

}
