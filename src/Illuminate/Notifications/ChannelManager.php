<?php

namespace Illuminate\Notifications;

use Illuminate\Mail\Markdown;
use InvalidArgumentException;
use Illuminate\Support\Manager;
use Nexmo\Client as NexmoClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Notifications\Channels\Factory as ChannelFactory;
use Illuminate\Contracts\Notifications\Channels\Dispatcher as ChannelDispatcher;

class ChannelManager extends Manager implements DispatcherContract, FactoryContract
{
    /**
     * The default channel used to deliver messages.
     *
     * @var string
     */
    protected $defaultChannel = 'mail';

    /**
     * Registered Channels factories.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        return (new NotificationSender(
            $this, $this->app->make(Bus::class), $this->app->make(Dispatcher::class))
        )->send($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        return (new NotificationSender(
            $this, $this->app->make(Bus::class), $this->app->make(Dispatcher::class))
        )->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Get a channel instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Illuminate\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Channels\DatabaseChannel::class);
    }

    /**
     * Create an instance of the broadcast driver.
     *
     * @return \Illuminate\Notifications\Channels\BroadcastChannel
     */
    protected function createBroadcastDriver()
    {
        return $this->app->make(Channels\BroadcastChannel::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * @return \Illuminate\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->app->make(Channels\MailChannel::class)->setMarkdownResolver(function () {
            return $this->app->make(Markdown::class);
        });
    }

    /**
     * Create an instance of the Nexmo driver.
     *
     * @return \Illuminate\Notifications\Channels\NexmoSmsChannel
     */
    protected function createNexmoDriver()
    {
        return new Channels\NexmoSmsChannel(
            new NexmoClient(new NexmoCredentials(
                $this->app['config']['services.nexmo.key'],
                $this->app['config']['services.nexmo.secret']
            )),
            $this->app['config']['services.nexmo.sms_from']
        );
    }

    /**
     * Create an instance of the Slack driver.
     *
     * @return \Illuminate\Notifications\Channels\SlackWebhookChannel
     */
    protected function createSlackDriver()
    {
        return new Channels\SlackWebhookChannel(new HttpClient);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        try {
            $channel = $this->createChannel($driver);

            if($channel instanceof ChannelDispatcher) {
                return $channel;
            }

            return parent::createDriver($driver);
        }
        catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Create a new driver instance.
     *
     * @param  $driver
     * @return null|\Illuminate\Contracts\Notifications\Channels\Dispatcher
     */
    protected function createChannel($driver)
    {
        foreach($this->channels as $channel) {
            if ($channel::canHandleNotification($driver) &&
                $channel = $channel::createDriver($driver)) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * Register a new channel driver.
     *
     * @param  string $channel
     * @return void
     * @throws \InvalidArgumentException
     */
    public function register($channel)
    {
        if (! (new \ReflectionClass($channel))->implementsInterface(ChannelFactory::class)) {
            throw new InvalidArgumentException(sprintf(
                "class [$channel] is not a valid implementation of '%s' interface.",
                ChannelFactory::class
            ));
        }

        $this->channels[] = $channel;
    }

    /**
     * Get all of the registered channels.
     *
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannel;
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function deliversVia()
    {
        return $this->getDefaultDriver();
    }

    /**
     * Set the default channel driver name.
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel)
    {
        $this->defaultChannel = $channel;
    }
}
