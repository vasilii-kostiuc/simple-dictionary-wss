<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Handlers\MessageHandlerInterface;
use App\WebSockets\Storage\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandler implements MessageHandlerInterface
{

    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg)
    {
        // TODO: Implement handle() method.
    }
}
