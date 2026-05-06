<?php

namespace App\Infrastructure\Metrics;

interface WsMetricsInterface
{
    public function connectionOpened(): void;

    public function connectionClosed(): void;

    public function messageReceived(string $type): void;

    public function invalidJsonReceived(): void;

    public function errorOccurred(): void;

    public function subscriptionAttempted(string $channel, string $action, string $result): void;

    public function activeSubscriptionAdded(string $channel): void;

    public function activeSubscriptionRemoved(string $channel): void;

    public function activeUserConnected(string $type): void;

    public function activeUserDisconnected(string $type): void;

    public function matchmakingQueueUserJoined(): void;

    public function matchmakingQueueUserLeft(): void;
}
