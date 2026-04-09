<?php

namespace App\WebSockets\Handlers\Client;

use App\WebSockets\Messages\ErrorMessage;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class UnknownMessageHandler implements MessageHandlerInterface
{
    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $from->send(new ErrorMessage('unknown_message', $msg->getPayload()));
    }
}
