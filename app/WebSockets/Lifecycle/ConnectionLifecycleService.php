<?php

namespace App\WebSockets\Lifecycle;

use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleService
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
    ) {
    }

    public function onOpen(ConnectionInterface $conn): void
    {
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clientRegistry->forget($conn);
        $this->subscriptionsStorage->unsubscribeAll($conn);
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        Log::error(__METHOD__.' '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
    }
}
