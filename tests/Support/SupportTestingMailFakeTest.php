<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Testing\Fakes\MailFake;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingMailFakeTest extends TestCase
{
    /**
     * @var \Illuminate\Support\Testing\Fakes\MailFake
     */
    private $fake;

    /**
     * @var \Illuminate\Tests\Support\MailableStub
     */
    private $mailable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new MailFake;
        $this->mailable = new MailableStub;
    }

    public function testAssertSent()
    {
        try {
            $this->fake->assertSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was not sent.'));
        }

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        $this->fake->assertSent(MailableStub::class);
    }

    public function testAssertSentWhenRecipientHasPreferredLocale()
    {
        $user = new LocalizedRecipientStub;

        $this->fake->to($user)->send($this->mailable);

        $this->fake->assertSent(MailableStub::class, static function ($mail) use ($user) {
            return $mail->hasTo($user) && $mail->locale === 'au';
        });
    }

    public function testAssertNotSent()
    {
        $this->fake->assertNotSent(MailableStub::class);

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNotSent(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\MailableStub] mailable was sent.'));
        }
    }

    public function testAssertSentTimes()
    {
        $this->fake->to('taylor@laravel.com')->send($this->mailable);
        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertSent(MailableStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was sent 2 times instead of 1 times.'));
        }

        $this->fake->assertSent(MailableStub::class, 2);
    }

    public function testAssertQueued()
    {
        try {
            $this->fake->assertQueued(MailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was not queued.'));
        }

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        $this->fake->assertQueued(MailableStub::class);
    }

    public function testAssertQueuedTimes()
    {
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);
        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertQueued(MailableStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\MailableStub] mailable was queued 2 times instead of 1 times.'));
        }

        $this->fake->assertQueued(MailableStub::class, 2);
    }

    public function testSendQueuesAMailableThatShouldBeQueued()
    {
        $this->fake->to('taylor@laravel.com')->send(new QueueableMailableStub);

        $this->fake->assertQueued(QueueableMailableStub::class);

        try {
            $this->fake->assertSent(QueueableMailableStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\QueueableMailableStub] mailable was not sent.'));
        }
    }

    public function testAssertNothingSent()
    {
        $this->fake->assertNothingSent();

        $this->fake->to('taylor@laravel.com')->send($this->mailable);

        try {
            $this->fake->assertNothingSent();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The following mailables were sent unexpectedly: Illuminate\Tests\Support\MailableStub'));
        }
    }

    public function testAssertNothingQueued()
    {
        $this->fake->assertNothingQueued();

        $this->fake->to('taylor@laravel.com')->queue($this->mailable);

        try {
            $this->fake->assertNothingQueued();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The following mailables were queued unexpectedly: Illuminate\Tests\Support\MailableStub'));
        }
    }

    public function testAssertQueuedWithClosure()
    {
        $this->fake->to($user = new LocalizedRecipientStub)->queue($this->mailable);

        $this->fake->assertQueued(static function (MailableStub $mail) use ($user) {
            return $mail->hasTo($user);
        });
    }

    public function testAssertSentWithClosure()
    {
        $this->fake->to($user = new LocalizedRecipientStub)->send($this->mailable);

        $this->fake->assertSent(static function (MailableStub $mail) use ($user) {
            return $mail->hasTo($user);
        });
    }
}

class MailableStub extends Mailable implements MailableContract
{
    public $framework = 'Laravel';

    protected $version = '6.0';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
             ->withLastName('Otwell');
    }
}

class QueueableMailableStub extends Mailable implements ShouldQueue
{
    public $framework = 'Laravel';

    protected $version = '6.0';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
             ->withLastName('Otwell');
    }
}

class LocalizedRecipientStub implements HasLocalePreference
{
    public $email = 'taylor@laravel.com';

    public function preferredLocale()
    {
        return 'au';
    }
}
