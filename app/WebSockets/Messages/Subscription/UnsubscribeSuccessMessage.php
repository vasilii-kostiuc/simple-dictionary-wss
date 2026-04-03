<?php

namespace App\WebSockets\Messages\Subscription;

use App\WebSockets\Messages\WebSocketMessage;

class UnsubscribeSuccessMessage extends WebSocketMessage
{
    public function __construct(string $channel)
    {
        parent::__construct('unsubscribe_success', [
            'channel' => $channel,
        ]);
    }
}
