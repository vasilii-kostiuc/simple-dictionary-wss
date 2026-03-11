<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;

class MatchMakingJoinTest extends WebSocketTestCase
{
    public function test_matchmaking_join_returns_success_message(): void
    {
        $client = $this->createWebSocketClient();

        $this->authenticateClient($client);
        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info("Received matchmaking.join response: " . $response->getPayload());

        $this->assertEquals('matchmaking_join_success', $payload->type ?? null);

        $client->close();
    }

    public function test_matchmaking_join_publishes_event_to_redis(): void
    {
        $client = $this->createWebSocketClient();

        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));

        $client->receive();

        sleep(1);

        $queueKeys = Redis::keys('matchmaking:queue:*');
        $this->assertNotEmpty($queueKeys, 'Expected matchmaking queue key in Redis');

        $members = Redis::zrange($queueKeys[0], 0, -1);
        $this->assertNotEmpty($members, 'Expected at least one user in matchmaking queue');

        $client->close();
    }

    public function test_matchmaking_join_with_default_match_type(): void
    {
        $client = $this->createWebSocketClient();

        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'matchmaking.join',
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals('matchmaking_join_success', $payload->type ?? null);
        $this->assertEquals('steps', $payload->data->match_type ?? null);

        $client->close();
    }

    public function test_matchmaking_join_with_extra_match_params(): void
    {
        $client = $this->createWebSocketClient();

        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'time',
            'match_params' => ['difficulty' => 'hard'],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals('matchmaking_join_success', $payload->type ?? null);
        $this->assertEquals('time', $payload->data->match_type ?? null);

        $client->close();
    }

    public function test_matchmaking_join_requires_auth(): void
    {
        $client = $this->createWebSocketClient();

        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info("Received matchmaking.join response without auth: " . $response->getPayload());
        $this->assertEquals('error', $payload->type ?? null);
        $this->assertEquals('not_authorized', $payload->data->error ?? null);

        $client->close();
    }
}

