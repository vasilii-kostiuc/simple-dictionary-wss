<?php

namespace Tests\Feature;

use App\WebSockets\Enums\ErrorCode;
use App\WebSockets\Enums\ServerEventType;

class GuestAuthTest extends WebSocketTestCase
{
    public function test_guest_auth_without_id_creates_new_guest(): void
    {
        $client = $this->createWebSocketClient();

        $client->text(json_encode([
            'type' => 'guest_auth',
            'data' => [],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received guest_auth response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::GuestAuthSuccess->value, $payload->type ?? null);
        $this->assertNotEmpty($payload->data->guest_id ?? null);

        $client->close();
    }

    public function test_guest_auth_with_known_guest_id_returns_that_guest(): void
    {
        $client = $this->createWebSocketClient();
        $guestId = '550e8400-e29b-41d4-a716-446655440000';

        $client->text(json_encode([
            'type' => 'guest_auth',
            'data' => ['guest_id' => $guestId],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received guest_auth with id response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::GuestAuthSuccess->value, $payload->type ?? null);
        $this->assertEquals($guestId, $payload->data->guest_id ?? null);

        $client->close();
    }

    public function test_guest_auth_with_invalid_guest_id_returns_error(): void
    {
        $client = $this->createWebSocketClient();

        $client->text(json_encode([
            'type' => 'guest_auth',
            'data' => ['guest_id' => 'not-a-valid-uuid'],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received guest_auth invalid id response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::InvalidGuestId->value, $payload->data->error ?? null);

        $client->close();
    }
}
