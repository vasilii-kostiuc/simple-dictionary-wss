<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Messages\ErrorMessage;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;


class UnknownMessageHandler implements MessageHandlerInterface
{
    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        info(__METHOD__);
        info($msg);

        $from->send(new ErrorMessage('unknown_message', $msg->getPayload()));
    }
}
