<?php

use Mockery as m;
use Illuminate\Mail\Message;

class MailMessageTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicAttachment()
	{
		$swift = m::mock('StdClass');
		$message = $this->getMock(Message::class, array('createAttachmentFromPath'), array($swift));
		$attachment = m::mock('StdClass');
		$message->expects($this->once())->method('createAttachmentFromPath')->with($this->equalTo('foo.jpg'))->will($this->returnValue($attachment));
		$swift->shouldReceive('attach')->once()->with($attachment);
		$attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
		$attachment->shouldReceive('setFilename')->once()->with('bar.jpg');
		$message->attach('foo.jpg', array('mime' => 'image/jpeg', 'as' => 'bar.jpg'));
	}


	public function testDataAttachment()
	{
		$swift = m::mock('StdClass');
		$message = $this->getMock(Message::class, array('createAttachmentFromData'), array($swift));
		$attachment = m::mock('StdClass');
		$message->expects($this->once())->method('createAttachmentFromData')->with($this->equalTo('foo'), $this->equalTo('name'))->will($this->returnValue($attachment));
		$swift->shouldReceive('attach')->once()->with($attachment);
		$attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
		$message->attachData('foo', 'name', array('mime' => 'image/jpeg'));
	}

}
