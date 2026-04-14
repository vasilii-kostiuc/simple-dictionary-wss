<?php

namespace App\WebSockets\Broker;

use App\WebSockets\Handlers\Internal\InternalMessageHandlerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class InternalBrokerSubscriber
{
    public function __construct(
        private readonly InternalMessageHandlerFactory $internalMessageHandlerFactory,
    ) {
    }

    public function subscribe(MessageBrokerInterface $messageBroker): void
    {
        $subscribeCallback = function (string $message): void {
            $data = json_decode($message, true) ?? [];
            $type = $data['type'] ?? '';
            $handler = $this->internalMessageHandlerFactory->create($type);

            $handler->handle($data);
        };

        $messageBroker->subscribe('wss.matchmaking.joined', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.leaved', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.matched', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.queue.updated', $subscribeCallback);
        $messageBroker->subscribe('wss.link_match_room.joined', $subscribeCallback);
        $messageBroker->subscribe('wss.link_match_room.left', $subscribeCallback);
    }
}
