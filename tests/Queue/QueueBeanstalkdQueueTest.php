<?php

use Mockery as m;

class QueueBeanstalkdQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPushProperlyPushesJobOntoBeanstalkd()
	{
		$queue = new Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk_Pheanstalk'), 'default', 60);
		$pheanstalk = $queue->getPheanstalk();
		$pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
		$pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
		$pheanstalk->shouldReceive('put')->twice()->with(json_encode(['job' => 'foo', 'data' => ['data']]), 1024, 0, 60);

		$queue->push('foo', ['data'], 'stack');
		$queue->push('foo', ['data']);
	}


	public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
	{
		$queue = new Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk_Pheanstalk'), 'default', 60);
		$pheanstalk = $queue->getPheanstalk();
		$pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
		$pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
		$pheanstalk->shouldReceive('put')->twice()->with(json_encode(['job' => 'foo', 'data' => ['data']]), Pheanstalk_Pheanstalk::DEFAULT_PRIORITY, 5);

		$queue->later(5, 'foo', ['data'], 'stack');
		$queue->later(5, 'foo', ['data']);
	}


	public function testPopProperlyPopsJobOffOfBeanstalkd()
	{
		$queue = new Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk_Pheanstalk'), 'default', 60);
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$pheanstalk = $queue->getPheanstalk();
		$pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
		$job = m::mock('Pheanstalk_Job');
		$pheanstalk->shouldReceive('reserve')->once()->andReturn($job);

		$result = $queue->pop();

		$this->assertInstanceOf('Illuminate\Queue\Jobs\BeanstalkdJob', $result);
	}

}
