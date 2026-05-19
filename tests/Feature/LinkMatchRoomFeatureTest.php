<?php

namespace Tests\Feature;

use App\WebSockets\Enums\ErrorCode;
use App\WebSockets\Enums\ServerEventType;

class LinkMatchRoomFeatureTest extends WebSocketTestCase
{
    public function test_join_link_match_room_returns_room_changed(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'link_match_room.join',
            'data' => ['link_token' => 'test-token-'.uniqid()],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received link_match_room.join response: '.$response->getPayload());

        $this->assertEquals('match_room.changed', $payload->type ?? null);
        $this->assertNotEmpty($payload->data->room_id ?? null);
        $this->assertIsArray($payload->data->participants ?? null);

        $client->close();
    }

    public function test_leave_link_match_room_returns_room_changed(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $token = 'test-token-'.uniqid();

        $client->text(json_encode([
            'type' => 'link_match_room.join',
            'data' => ['link_token' => $token],
        ]));
        $client->receive();

        $client->text(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => $token],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received link_match_room.leave response: '.$response->getPayload());

        $this->assertEquals('match_room.changed', $payload->type ?? null);

        $client->close();
    }

    public function test_join_without_link_token_returns_error(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'link_match_room.join',
            'data' => [],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received link_match_room.join no token response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::LinkNotFound->value, $payload->data->error ?? null);

        $client->close();
    }

    public function test_join_link_match_room_requires_auth(): void
    {
        $client = $this->createWebSocketClient();

        $client->text(json_encode([
            'type' => 'link_match_room.join',
            'data' => ['link_token' => 'some-token'],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received link_match_room.join no auth response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::NotAuthorized->value, $payload->data->error ?? null);

        $client->close();
    }
}
