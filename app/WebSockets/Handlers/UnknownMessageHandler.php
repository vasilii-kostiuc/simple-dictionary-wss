<?php

namespace App\WebSockets\Handlers;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;


class UnknownMessageHandler implements MessageHandlerInterface
{
    public function handle(ConnectionInterface $from, MessageInterface $msg)
    {
        info(__METHOD__);
        info($msg);

        $from->send(json_encode([
            'type' => 'error',
            'data' => [
                'client_payload' => [
                    $msg->getPayload()
                ]
            ]
        ]));
    }
}
