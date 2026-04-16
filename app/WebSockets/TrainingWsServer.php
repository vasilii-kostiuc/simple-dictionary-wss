<?php

namespace App\WebSockets;

use App\WebSockets\Dispatch\ClientMessageDispatcher;
use App\WebSockets\Lifecycle\ConnectionLifecycleService;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class TrainingWsServer implements MessageComponentInterface
{
    public function __construct(
        protected readonly ConnectionLifecycleService $connectionLifecycleService,
        protected readonly ClientMessageDispatcher $clientMessageDispatcher,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->connectionLifecycleService->onOpen($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->connectionLifecycleService->onClose($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->connectionLifecycleService->onError($conn, $e);
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        $this->clientMessageDispatcher->dispatch($conn, $msg);
    }
}
