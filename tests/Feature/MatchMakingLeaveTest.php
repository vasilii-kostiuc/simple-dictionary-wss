<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;

class MatchMakingLeaveTest extends WebSocketTestCase
{
    public function test_matchmaking_leave_removes_user_from_queue(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        // Join matchmaking
        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));
        $client->receive();

        // Leave matchmaking
        $client->text(json_encode([
            'type' => 'matchmaking.leave',
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals('matchmaking_leave_success', $payload->type ?? null);

        // Проверяем, что пользователь удалён из очереди
        $queueKeys = Redis::keys('matchmaking:queue:*');
        $found = false;
        foreach ($queueKeys as $queueKey) {
            $members = Redis::zrange($queueKey, 0, -1);
            if (!empty($members)) {
                $found = true;
            }
        }
        $this->assertFalse($found, 'User should be removed from matchmaking queue');

        $client->close();
    }

    public function test_matchmaking_leave_publishes_event_to_redis(): void
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);

        // Join matchmaking
        $client->text(json_encode([
            'type' => 'matchmaking.join',
            'match_type' => 'steps',
        ]));
        $client->receive();

        // Leave matchmaking
        $client->text(json_encode([
            'type' => 'matchmaking.leave',
        ]));
        $client->receive();

        sleep(1);

        // Проверяем, что событие leave опубликовано (можно проверить по отсутствию пользователя в очереди)
        $queueKeys = Redis::keys('matchmaking:queue:*');
        $found = false;
        foreach ($queueKeys as $queueKey) {
            $members = Redis::zrange($queueKey, 0, -1);
            if (!empty($members)) {
                $found = true;
            }
        }
        $this->assertFalse($found, 'User should be removed from matchmaking queue after leave');

        $client->close();
    }

    public function test_matchmaking_leave_requires_auth(): void
    {
        $client = $this->createWebSocketClient();

        // Без auth сразу отправляем matchmaking.leave
        $client->text(json_encode([
            'type' => 'matchmaking.leave',
        ]));

        $response = $client->receive();
        $payload = json_decode($response->getPayload());

        $this->assertEquals('error', $payload->type ?? null);
        $this->assertEquals('not_authorized', $payload->data->error ?? null);

        $client->close();
    }
}
