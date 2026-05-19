<?php

namespace Tests\Feature;

use App\WebSockets\Enums\ErrorCode;
use App\WebSockets\Enums\ServerEventType;

class SubscribeUnsubscribeTest extends WebSocketTestCase
{
    public function test_unsubscribe_from_channel_returns_success(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);
        $this->subscribeClient($client, 'training.121');

        $client->text(json_encode([
            'type' => 'unsubscribe',
            'data' => ['channel' => 'training.121'],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received unsubscribe response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::UnsubscribeSuccess->value, $payload->type ?? null);
        $this->assertEquals('training.121', $payload->data->channel ?? null);

        $client->close();
    }

    public function test_unsubscribe_from_unknown_channel_returns_error(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'unsubscribe',
            'data' => ['channel' => 'unknown.channel'],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received unsubscribe unknown channel response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::ChannelIsNotAllowed->value, $payload->data->error ?? null);

        $client->close();
    }

    public function test_unknown_message_type_returns_error(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        $client->text(json_encode([
            'type' => 'some_unknown_type',
            'data' => [],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        info('Received unknown message type response: '.$response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::UnknownMessage->value, $payload->data->error ?? null);

        $client->close();
    }
}
