<?php

namespace App\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;

class WsMetrics
{
    private Counter $connectionsOpenedTotal;

    private Counter $connectionsClosedTotal;

    private Gauge $connectionsActive;

    private Counter $messagesTotal;

    private Counter $invalidJsonTotal;

    private Counter $errorsTotal;

    private Counter $subscriptionsTotal;

    private Gauge $subscriptionsActive;

    private string $nodeId;

    public function __construct(private readonly CollectorRegistry $registry)
    {
        $ns = (string) env('PROMETHEUS_NAMESPACE', 'wss');
        $this->nodeId = (string) env('WSS_NODE_ID', gethostname());

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

        $this->subscriptionsTotal = $registry->getOrRegisterCounter(
            $ns, 'subscriptions_total', 'Total channel subscription actions', ['node', 'channel_group', 'action'],
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

    public function subscribed(string $channel): void
    {
        $channelGroup = $this->normalizeChannelGroup($channel);

        $this->subscriptionsTotal->inc([$this->nodeId, $channelGroup, 'subscribe']);
        $this->subscriptionsActive->inc([$this->nodeId, $channelGroup]);
    }

    public function unsubscribed(string $channel): void
    {
        $channelGroup = $this->normalizeChannelGroup($channel);

        $this->subscriptionsTotal->inc([$this->nodeId, $channelGroup, 'unsubscribe']);
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
