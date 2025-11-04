<?php

namespace App\WebSockets\Handlers;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

interface MessageHandlerInterface
{
    public function handle(ConnectionInterface $from, MessageInterface $msg): void;

}
