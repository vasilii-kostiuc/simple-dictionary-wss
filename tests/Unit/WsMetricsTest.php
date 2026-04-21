<?php

namespace Tests\Unit;

use App\Infrastructure\Metrics\WsMetrics;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

class WsMetricsTest extends TestCase
{
    public function test_tracks_connection_and_json_metrics(): void
    {
        $registry = new CollectorRegistry(new InMemory);
        $metrics = new WsMetrics($registry);

        $metrics->connectionOpened();
        $metrics->messageReceived('subscribe');
        $metrics->invalidJsonReceived();
        $metrics->connectionClosed();

        $rendered = (new RenderTextFormat)->render($registry->getMetricFamilySamples());

        $this->assertStringContainsString('wss_connections_opened_total', $rendered);
        $this->assertStringContainsString('wss_connections_closed_total', $rendered);
        $this->assertStringContainsString('wss_messages_total{node=', $rendered);
        $this->assertStringContainsString('type="subscribe"', $rendered);
        $this->assertStringContainsString('wss_messages_invalid_json_total', $rendered);
    }

    public function test_normalizes_subscription_channel_groups(): void
    {
        $registry = new CollectorRegistry(new InMemory);
        $metrics = new WsMetrics($registry);

        $metrics->subscriptionAttempted('training.121', 'subscribe', 'success');
        $metrics->activeSubscriptionAdded('training.121');
        $metrics->subscriptionAttempted('link_match_room.room-123', 'subscribe', 'success');
        $metrics->activeSubscriptionAdded('link_match_room.room-123');
        $metrics->subscriptionAttempted('matchmaking.queue', 'unsubscribe', 'success');
        $metrics->activeSubscriptionRemoved('matchmaking.queue');

        $rendered = (new RenderTextFormat)->render($registry->getMetricFamilySamples());

        $this->assertStringContainsString('channel_group="training"', $rendered);
        $this->assertStringContainsString('channel_group="link_match_room"', $rendered);
        $this->assertStringContainsString('channel_group="matchmaking_queue"', $rendered);
        $this->assertStringContainsString('action="subscribe"', $rendered);
        $this->assertStringContainsString('action="unsubscribe"', $rendered);
        $this->assertStringContainsString('result="success"', $rendered);
        $this->assertStringContainsString('wss_subscription_attempts_total', $rendered);
        $this->assertStringContainsString('wss_subscriptions_active', $rendered);
    }
}
