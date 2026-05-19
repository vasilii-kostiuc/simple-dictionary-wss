<?php

namespace App\WebSockets\Broker;

use VasiliiKostiuc\PubSubBroker\Messaging\BrokerInterface;

class BrokerSubscriptionsBootstrapper
{
    public function __construct(
        private readonly ApiBrokerSubscriber $apiBrokerSubscriber,
        private readonly InternalBrokerSubscriber $internalBrokerSubscriber,
    ) {}

    public function bootstrap(BrokerInterface $messageBroker): void
    {
        $this->apiBrokerSubscriber->subscribe($messageBroker);
        $this->internalBrokerSubscriber->subscribe($messageBroker);
    }
}
