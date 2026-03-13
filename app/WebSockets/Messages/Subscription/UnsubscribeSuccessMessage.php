<?php

namespace App\WebSockets\Messages\Subscription;

class UnsubscribeSuccessMessage
{
    protected string $channel;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function __toString(): string
    {
        return json_encode([
            'type' => 'unsubscribe_success',
            'channel' => $this->channel,
        ]);
    }
}
