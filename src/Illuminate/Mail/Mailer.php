<?php

namespace Illuminate\Mail;

use Closure;
use Exception;
use Throwable;
use Swift_Mailer;
use Swift_Message;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;

class Mailer implements MailerContract, MailQueueContract
{
    /**
     * The view factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * The Swift Mailer instance.
     *
     * @var \Swift_Mailer
     */
    protected $swift;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from address and name.
     *
     * @var array
     */
    protected $from;

    /**
     * The global reply-to address and name.
     *
     * @var array
     */
    protected $replyTo;

    /**
     * The global to address and name.
     *
     * @var array
     */
    protected $to;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The queue implementation.
     *
     * @var \Illuminate\Contracts\Queue\Queue
     */
    protected $queue;

    /**
     * Array of failed recipients.
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     * Create a new Mailer instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @param  \Swift_Mailer  $swift
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(Factory $views, Swift_Mailer $swift, Dispatcher $events = null)
    {
        $this->views = $views;
        $this->swift = $swift;
        $this->events = $events;
    }

    /**
     * Set the global from address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->from = compact('address', 'name');
    }

    /**
     * Set the global reply-to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysReplyTo($address, $name = null)
    {
        $this->replyTo = compact('address', 'name');
    }

    /**
     * Set the global to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->to = compact('address', 'name');
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return MailableMailer
     */
    public function to($users)
    {
        return (new MailableMailer($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return MailableMailer
     */
    public function bcc($users)
    {
        return (new MailableMailer($this))->bcc($users);
    }

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Send a new message when only a plain part.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  mixed  $callback
     * @return void
     */
    public function plain($view, array $data, $callback)
    {
        return $this->send(['text' => $view], $data, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            return $view->send($this);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        $this->addContent($message, $view, $plain, $raw, $data);

        $this->callMessageBuilder($callback, $message);

        if (isset($this->to['address'])) {
            $message->to($this->to['address'], $this->to['name'], true);
            $message->cc($this->to['address'], $this->to['name'], true);
            $message->bcc($this->to['address'], $this->to['name'], true);
        }

        $message = $message->getSwiftMessage();

        $this->sendSwiftMessage($message);
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, array $data = [], $callback = null, $queue = null)
    {
        if ($view instanceof MailableContract) {
            return $view->queue($this->queue);
        }

        return $this->queue->pushOn(
            $queue, new Jobs\HandleQueuedMessage($view, $data, $callback)
        );
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function onQueue($queue, $view, array $data, $callback)
    {
        return $this->queue($view, $data, $callback, $queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * This method didn't match rest of framework's "onQueue" phrasing. Added "onQueue".
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function queueOn($queue, $view, array $data, $callback)
    {
        return $this->onQueue($queue, $view, $data, $callback);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, array $data = [], $callback = null, $queue = null)
    {
        if ($view instanceof MailableContract) {
            return $view->later($delay, $this->queue);
        }

        return $this->queue->laterOn(
            $queue, $delay, new Jobs\HandleQueuedMessage($view, $data, $callback)
        );
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function laterOn($queue, $delay, $view, array $data, $callback)
    {
        return $this->later($delay, $view, $data, $callback, $queue);
    }

    /**
     * Force the transport to re-connect.
     *
     * This will prevent errors in daemon queue situations.
     *
     * @return void
     */
    protected function forceReconnection()
    {
        $this->getSwiftMailer()->getTransport()->stop();
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $view
     * @param  string  $plain
     * @param  string  $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            $message->setBody($this->getView($view, $data), 'text/html');
        }

        if (isset($plain)) {
            $method = isset($view) ? 'addPart' : 'setBody';

            $message->$method($this->getView($plain, $data), 'text/plain');
        }

        if (isset($raw)) {
            $method = (isset($view) || isset($plain)) ? 'addPart' : 'setBody';

            $message->$method($raw, 'text/plain');
        }
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since must should contain both views with numeric keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If the view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via
        // named keys instead, allowing the devs to use one or the other.
        if (is_array($view)) {
            return [
                Arr::get($view, 'html'),
                Arr::get($view, 'text'),
                Arr::get($view, 'raw'),
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Send a Swift Message instance.
     *
     * @param  \Swift_Message  $message
     * @return void
     */
    protected function sendSwiftMessage($message)
    {
        if ($this->events) {
            $this->events->fire(new Events\MessageSending($message));
        }

        try {
            $result = $this->swift->send($message, $this->failedRecipients);
            if ($this->events) {
                $this->events->fire(new Events\MessageSent($message, $result, get_class($this->swift->getTransport())));
            }

            return $result;
        } catch (Exception $e) {
            if ($this->events) {
                $this->events->fire(new Events\MessageNotSent($message, $e, get_class($this->swift->getTransport())));
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->events) {
                $this->events->fire(new Events\MessageNotSent($message, $e, get_class($this->swift->getTransport())));
            }
            throw $e;
        } finally {
            $this->forceReconnection();
        }
    }

    /**
     * Call the provided message builder.
     *
     * @param  \Closure|string  $callback
     * @param  \Illuminate\Mail\Message  $message
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function callMessageBuilder($callback, $message)
    {
        if ($callback instanceof Closure) {
            return call_user_func($callback, $message);
        }

        if (is_string($callback)) {
            return $this->container->make($callback)->mail($message);
        }

        throw new InvalidArgumentException('Callback is not valid.');
    }

    /**
     * Create a new message instance.
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message(new Swift_Message);

        // If a global from address has been specified we will set it on every message
        // instances so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push the address.
        if (! empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instances so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push the address.
        if (! empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        return $message;
    }

    /**
     * Render the given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function getView($view, $data)
    {
        return $this->views->make($view, $data)->render();
    }

    /**
     * Get the view factory instance.
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function getViewFactory()
    {
        return $this->views;
    }

    /**
     * Get the Swift Mailer instance.
     *
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }

    /**
     * Get the array of failed recipients.
     *
     * @return array
     */
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Set the Swift Mailer instance.
     *
     * @param  \Swift_Mailer  $swift
     * @return void
     */
    public function setSwiftMailer($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $queue
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
