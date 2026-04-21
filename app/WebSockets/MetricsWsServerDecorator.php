<?php

namespace App\WebSockets;

use App\Infrastructure\Metrics\WsMetrics;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class MetricsWsServerDecorator implements MessageComponentInterface
{
    public function __construct(
        private readonly MessageComponentInterface $inner,
        private readonly WsMetrics $metrics,
    ) {}

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->metrics->connectionOpened();
        $this->inner->onOpen($conn);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->metrics->connectionClosed();
        $this->inner->onClose($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->metrics->errorOccurred();
        $this->inner->onError($conn, $e);
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $type = $payload['type'] ?? 'unknown';

        $this->metrics->messageReceived((string) $type);
        $this->inner->onMessage($conn, $msg);
    }
}
