<?php

namespace App\WebSockets\Lifecycle;

use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleService
{
    public function __construct(
        private readonly ClientsStorageInterface $storage,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
    ) {
    }

    public function onOpen(ConnectionInterface $conn): void
    {
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->storage->remove($conn);
        $this->subscriptionsStorage->unsubscribeAll($conn);
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        Log::error(__METHOD__.' '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
    }
}
