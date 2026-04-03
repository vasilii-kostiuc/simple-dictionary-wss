<?php

namespace App\WebSockets\Broker;

use App\WebSockets\Handlers\Api\ApiMessageHandlerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class ApiBrokerSubscriber
{
    public function __construct(
        private readonly ApiMessageHandlerFactory $apiMessageHandlerFactory,
    ) {
    }

    public function subscribe(MessageBrokerInterface $messageBroker): void
    {
        $subscribeCallback = function (string $message): void {
            $data = json_decode($message, true) ?? [];
            $type = $data['type'] ?? '';
            $handler = $this->apiMessageHandlerFactory->create($type);

            $handler->handle($data);
        };

        $messageBroker->subscribe('api.training', $subscribeCallback);
        $messageBroker->subscribe('api.match', $subscribeCallback);
    }
}
