<?php

namespace App\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;

class WsMetrics implements WsMetricsInterface
{
    private Counter $connectionsOpenedTotal;

    private Counter $connectionsClosedTotal;

    private Gauge $connectionsActive;

    private Counter $messagesTotal;

    private Counter $invalidJsonTotal;

    private Counter $errorsTotal;

    private Counter $subscriptionAttemptsTotal;

    private Gauge $subscriptionsActive;

    private string $nodeId;

    public function __construct(private readonly CollectorRegistry $registry)
    {
        $ns = (string) config('metrics.namespace');
        $this->nodeId = (string) config('app.node_id');

        $this->connectionsOpenedTotal = $registry->getOrRegisterCounter(
            $ns, 'connections_opened_total', 'Total WebSocket connections opened', ['node'],
        );

        $this->connectionsClosedTotal = $registry->getOrRegisterCounter(
            $ns, 'connections_closed_total', 'Total WebSocket connections closed', ['node'],
        );

        $this->connectionsActive = $registry->getOrRegisterGauge(
            $ns, 'connections_active', 'Currently active WebSocket connections', ['node'],
        );

        $this->messagesTotal = $registry->getOrRegisterCounter(
            $ns, 'messages_total', 'Total client messages received', ['node', 'type'],
        );

        $this->invalidJsonTotal = $registry->getOrRegisterCounter(
            $ns, 'messages_invalid_json_total', 'Total invalid JSON client messages received', ['node'],
        );

        $this->errorsTotal = $registry->getOrRegisterCounter(
            $ns, 'errors_total', 'Total WebSocket errors', ['node'],
        );

        $this->subscriptionAttemptsTotal = $registry->getOrRegisterCounter(
            $ns, 'subscription_attempts_total', 'Total channel subscription attempts', ['node', 'channel_group', 'action', 'result'],
        );

        $this->subscriptionsActive = $registry->getOrRegisterGauge(
            $ns, 'subscriptions_active', 'Currently active channel subscriptions', ['node', 'channel_group'],
        );
    }

    public function connectionOpened(): void
    {
        $this->connectionsOpenedTotal->inc([$this->nodeId]);
        $this->connectionsActive->inc([$this->nodeId]);
    }

    public function connectionClosed(): void
    {
        $this->connectionsClosedTotal->inc([$this->nodeId]);
        $this->connectionsActive->dec([$this->nodeId]);
    }

    public function messageReceived(string $type): void
    {
        $normalizedType = $type !== '' ? $type : 'unknown';

        $this->messagesTotal->inc([$this->nodeId, $normalizedType]);
    }

    public function invalidJsonReceived(): void
    {
        $this->invalidJsonTotal->inc([$this->nodeId]);
    }

    public function errorOccurred(): void
    {
        $this->errorsTotal->inc([$this->nodeId]);
    }

    public function subscriptionAttempted(string $channel, string $action, string $result): void
    {
        $channelGroup = $this->normalizeChannelGroup($channel);

        $this->subscriptionAttemptsTotal->inc([$this->nodeId, $channelGroup, $action, $result]);
    }

    public function activeSubscriptionAdded(string $channel): void
    {
        $channelGroup = $this->normalizeChannelGroup($channel);

        $this->subscriptionsActive->inc([$this->nodeId, $channelGroup]);
    }

    public function activeSubscriptionRemoved(string $channel): void
    {
        $channelGroup = $this->normalizeChannelGroup($channel);

        $this->subscriptionsActive->dec([$this->nodeId, $channelGroup]);
    }

    private function normalizeChannelGroup(string $channel): string
    {
        if ($channel === 'matchmaking.queue') {
            return 'matchmaking_queue';
        }

        if (str_starts_with($channel, 'training.')) {
            return 'training';
        }

        if (str_starts_with($channel, 'match.')) {
            return 'match';
        }

        if (str_starts_with($channel, 'link_match_room.')) {
            return 'link_match_room';
        }

        return 'other';
    }
}
