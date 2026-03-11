<?php

namespace Tests\Feature;

class MatchMakingQueueSubscribeTest extends WebSocketTestCase
{
    public function test_subscribe_to_matchmaking_queue_receives_updated_queue(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'subscribe',
            'channel' => 'matchmaking.queue',
        ]));

        $response = $client->receive();  // subscribe_success
        $response = $client->receive();
        $payload = json_decode($response->getPayload());
        info("Received matchmaking.queue response: " . $response->getPayload());

        $this->assertEquals('matchmaking.queue.updated', $payload->type ?? null);
        $this->assertObjectHasProperty('queue', $payload->data ?? []);

        $client->close();
    }

    public function test_queue_updates_after_join(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        // Subscribe to matchmaking.queue
        $client->text(json_encode([
            'type' => 'subscribe',
            'channel' => 'matchmaking.queue',
        ]));
        $client->receive(); // subscribe_success
        $client->receive(); // matchmaking.queue.updated

        // Join matchmaking
        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));

        $response = $client->receive();

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info("Received matchmaking.queue.updated response after join: " . $response->getPayload());

        $this->assertEquals('matchmaking.queue.updated', $payload->type ?? null);

        $this->assertObjectHasProperty('queue', $payload->data ?? []);

        $client->close();
    }
}
