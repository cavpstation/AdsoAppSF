<?php

use Mockery as m;
use Illuminate\Mail\Message;

class MailMessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery::mock
     */
    protected $swift;

    /**
     * @var \Illuminate\Mail\Message
     */
    protected $message;

    public function setUp()
    {
        parent::setUp();

        $this->swift = m::mock(Swift_Mime_Message::class);
        $this->message = new Message($this->swift);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testFromMethod()
    {
        $this->swift->shouldReceive('setFrom')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->from('foo@bar.baz', 'Foo'));
    }

    public function testSenderMethod()
    {
        $this->swift->shouldReceive('setSender')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->sender('foo@bar.baz', 'Foo'));
    }

    public function testReturnPathMethod()
    {
        $this->swift->shouldReceive('setReturnPath')->once()->with('foo@bar.baz');
        $this->assertInstanceOf(Message::class, $this->message->returnPath('foo@bar.baz'));
    }

    public function testToMethod()
    {
        $this->swift->shouldReceive('addTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', false));
    }

    public function testToMethodWithOverride()
    {
        $this->swift->shouldReceive('setTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->to('foo@bar.baz', 'Foo', true));
    }

    public function testCcMethod()
    {
        $this->swift->shouldReceive('addCc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->cc('foo@bar.baz', 'Foo'));
    }

    public function testBccMethod()
    {
        $this->swift->shouldReceive('addBcc')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->bcc('foo@bar.baz', 'Foo'));
    }

    public function testReplyToMethod()
    {
        $this->swift->shouldReceive('addReplyTo')->once()->with('foo@bar.baz', 'Foo');
        $this->assertInstanceOf(Message::class, $this->message->replyTo('foo@bar.baz', 'Foo'));
    }

    public function testSubjectMethod()
    {
        $this->swift->shouldReceive('setSubject')->once()->with('foo');
        $this->assertInstanceOf(Message::class, $this->message->subject('foo'));
    }

    public function testPriorityMethod()
    {
        $this->swift->shouldReceive('setPriority')->once()->with(1);
        $this->assertInstanceOf(Message::class, $this->message->priority(1));
    }

    public function testGetSwiftMessageMethod()
    {
        $this->assertInstanceOf(Swift_Mime_Message::class, $this->message->getSwiftMessage());
    }

    public function testBasicAttachment()
    {
        $swift = m::mock('StdClass');
        $message = $this->getMockBuilder('Illuminate\Mail\Message')->setMethods(['createAttachmentFromPath'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock('StdClass');
        $message->expects($this->once())->method('createAttachmentFromPath')->with($this->equalTo('foo.jpg'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $attachment->shouldReceive('setFilename')->once()->with('bar.jpg');
        $message->attach('foo.jpg', ['mime' => 'image/jpeg', 'as' => 'bar.jpg']);
    }

    public function testDataAttachment()
    {
        $swift = m::mock('StdClass');
        $message = $this->getMockBuilder('Illuminate\Mail\Message')->setMethods(['createAttachmentFromData'])->setConstructorArgs([$swift])->getMock();
        $attachment = m::mock('StdClass');
        $message->expects($this->once())->method('createAttachmentFromData')->with($this->equalTo('foo'), $this->equalTo('name'))->will($this->returnValue($attachment));
        $swift->shouldReceive('attach')->once()->with($attachment);
        $attachment->shouldReceive('setContentType')->once()->with('image/jpeg');
        $message->attachData('foo', 'name', ['mime' => 'image/jpeg']);
    }
}
