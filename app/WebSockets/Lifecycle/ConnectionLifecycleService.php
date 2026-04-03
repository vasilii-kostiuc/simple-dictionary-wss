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
        Log::info('New connection '.$conn->resourceId);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        Log::info(__METHOD__.' '.$conn->resourceId);

        $this->storage->remove($conn);
        $this->subscriptionsStorage->unsubscribeAll($conn);

        info(json_encode($this->subscriptionsStorage->getChannelsByConnection($conn)));
        Log::info(__METHOD__.' '.$conn->resourceId);
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        Log::error(__METHOD__.' '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
    }
}
