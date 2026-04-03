<?php

namespace App\WebSockets\Broker;

use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class BrokerSubscriptionsBootstrapper
{
    public function __construct(
        private readonly ApiBrokerSubscriber $apiBrokerSubscriber,
        private readonly InternalBrokerSubscriber $internalBrokerSubscriber,
    ) {
    }

    public function bootstrap(MessageBrokerInterface $messageBroker): void
    {
        $this->apiBrokerSubscriber->subscribe($messageBroker);
        $this->internalBrokerSubscriber->subscribe($messageBroker);
    }
}
