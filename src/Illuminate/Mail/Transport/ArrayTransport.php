<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_SimpleMessage;
use Illuminate\Collection\Collection;

class ArrayTransport extends Transport
{
    /**
     * The collection of Swift Messages.
     *
     * @var \Illuminate\Collection\Collection
     */
    protected $messages;

    /**
     * Create a new array transport instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->messages[] = $message;

        return $this->numberOfRecipients($message);
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return \Illuminate\Collection\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * @return \Illuminate\Collection\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}
