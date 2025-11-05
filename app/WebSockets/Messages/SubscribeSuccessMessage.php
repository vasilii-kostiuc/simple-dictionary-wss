<?php

namespace App\WebSockets\Messages;

class SubscribeSuccessMessage extends WebSocketMessage
{
    public function __construct(string $channel)
    {
        parent::__construct('subscribe_success', [
            'channel' => $channel,
        ]);
    }

}
