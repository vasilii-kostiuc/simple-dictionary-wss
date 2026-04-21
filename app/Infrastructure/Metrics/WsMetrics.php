<?php

namespace App\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;

class WsMetrics
{
    private Counter $connectionsTotal;

    private Gauge $connectionsActive;

    private Counter $messagesTotal;

    private Counter $errorsTotal;

    private Counter $subscriptionsTotal;

    private Counter $unsubscriptionsTotal;

    public function __construct(private readonly CollectorRegistry $registry)
    {
        $ns = 'wss';

        $this->connectionsTotal = $registry->getOrRegisterCounter(
            $ns, 'connections_total', 'Total WebSocket connections opened', ['node'],
        );

        $this->connectionsActive = $registry->getOrRegisterGauge(
            $ns, 'connections_active', 'Currently active WebSocket connections', ['node'],
        );

        $this->messagesTotal = $registry->getOrRegisterCounter(
            $ns, 'messages_total', 'Total client messages received', ['node', 'type'],
        );

        $this->errorsTotal = $registry->getOrRegisterCounter(
            $ns, 'errors_total', 'Total WebSocket errors', ['node'],
        );

        $this->subscriptionsTotal = $registry->getOrRegisterCounter(
            $ns, 'subscriptions_total', 'Total channel subscriptions', ['node', 'channel'],
        );

        $this->unsubscriptionsTotal = $registry->getOrRegisterCounter(
            $ns, 'unsubscriptions_total', 'Total channel unsubscriptions', ['node', 'channel'],
        );
    }

    public function connectionOpened(): void
    {
        $this->connectionsTotal->inc([$this->nodeId()]);
        $this->connectionsActive->inc([$this->nodeId()]);
    }

    public function connectionClosed(): void
    {
        $this->connectionsActive->dec([$this->nodeId()]);
    }

    public function messageReceived(string $type): void
    {
        $this->messagesTotal->inc([$this->nodeId(), $type]);
    }

    public function errorOccurred(): void
    {
        $this->errorsTotal->inc([$this->nodeId()]);
    }

    public function subscribed(string $channel): void
    {
        $this->subscriptionsTotal->inc([$this->nodeId(), $channel]);
    }

    public function unsubscribed(string $channel): void
    {
        $this->unsubscriptionsTotal->inc([$this->nodeId(), $channel]);
    }

    private function nodeId(): string
    {
        return (string) env('WSS_NODE_ID', gethostname());
    }
}
