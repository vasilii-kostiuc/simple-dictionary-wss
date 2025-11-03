<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Handlers\MessageHandlerInterface;
use App\WebSockets\Storage\AuthorizedClientsStorage;
use App\WebSockets\Storage\ClientsStorageInterface;
use App\WebSockets\Storage\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandler implements MessageHandlerInterface
{

    private SubscriptionsStorageInterface $subscriptionsStorage;
    private ClientsStorageInterface $clientsStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage, ClientsStorageInterface $clientsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->clientsStorage = $clientsStorage;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg)
    {
        info(__METHOD__);
        info($msg);
        $msgJson = json_decode($msg->getPayload());
        $channel = $msgJson->channel ?? "";

        $userId = $this->clientsStorage->getUserIdByConnection($from);

        if (empty($channel)) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => [
                    'message' => 'Channel is required'
                ]
            ]));
            return;
        }

        if ($userId === null) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => [
                    'message' => 'You are not authorized'
                ]
            ]));
            $from->close();
        }

        $this->subscriptionsStorage->subscribe($from, $channel);

    }
}
