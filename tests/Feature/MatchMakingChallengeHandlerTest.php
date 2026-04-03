<?php

namespace Tests\Feature;

use App\WebSockets\Enums\ErrorCode;
use App\WebSockets\Enums\ServerEventType;
use Illuminate\Support\Facades\Redis;

class MatchMakingChallengeHandlerTest extends WebSocketTestCase
{
    public function test_matchmaking_challenge_creates_match_when_opponent_in_queue(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client, 'token1');

        $opponent = $this->createWebSocketClient();
        $this->authenticateClient($opponent, 'token2');

        // Opponent joins queue first
        $opponent->text(json_encode([
            'type' => 'matchmaking.join',
            'data' => [
                'match_type' => 'steps',
            ],
        ]));
        $message = $opponent->receive(); // matchmaking_join_success

        // Challenge opponent
        $client->text(json_encode([
            'type' => 'matchmaking.challenge',
            'data' => [
                'opponent_id' => crc32('token2'),
            ],
        ]));

        $response = $client->receive();

        $payload = json_decode($response->getPayload());

        $this->assertEquals(ServerEventType::MatchmakingChallengeSuccess->value, $payload->type ?? null);

        $client->close();
        $opponent->close();
    }

    public function test_matchmaking_challenge_fails_when_opponent_not_in_queue(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client, 'token1');

        $client->text(json_encode([
            'type' => 'matchmaking.challenge',
            'data' => [
                'opponent_id' => 99999,
            ],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::OpponentNotInQueue->value, $payload->data->error ?? null);

        $client->close();
    }

    public function test_matchmaking_challenge_fails_without_opponent_id(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client, 'token1');

        $client->text(json_encode([
            'type' => 'matchmaking.challenge',
            'data' => [],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);
        $this->assertEquals(ErrorCode::OpponentIdRequired->value, $payload->data->error ?? null);

        $client->close();
    }

    public function test_matchmaking_challenge_requires_auth(): void
    {
        $client = $this->createWebSocketClient();

        $client->text(json_encode([
            'type' => 'matchmaking.challenge',
            'data' => [
                'opponent_id' => 123,
            ],
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals(ServerEventType::Error->value, $payload->type ?? null);

        $client->close();
    }

    public function test_matchmaking_challenge_removes_both_users_from_queue(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client, 'token1');

        $opponent = $this->createWebSocketClient();
        $this->authenticateClient($opponent, 'token2');

        // Both join queue
        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'data' => ['match_type' => 'steps'],
        ]));
        $client->receive();

        $opponent->text(json_encode([
            'type' => 'matchmaking.join',
            'data' => ['match_type' => 'steps'],
        ]));
        $opponent->receive();

        $opponentId = crc32('token2');

        // Challenge opponent
        $client->text(json_encode([
            'type' => 'matchmaking.challenge',
            'data' => ['opponent_id' => $opponentId],
        ]));
        $client->receive();

        // Verify both users are removed from queue
        sleep(1); // wait for Redis to be updated after challenge

        $this->assertFalse(
            (bool) Redis::exists('matchmaking:user:'.crc32('token1')),
            'User 1 should be removed from queue'
        );
        $this->assertFalse(
            (bool) Redis::exists('matchmaking:user:'.$opponentId),
            'Opponent should be removed from queue'
        );

        $client->close();
        $opponent->close();
    }
}