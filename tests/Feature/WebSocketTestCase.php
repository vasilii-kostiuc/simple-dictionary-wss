<?php

namespace Tests\Feature;

use Tests\TestCase;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

abstract class WebSocketTestCase extends TestCase
{
    protected const string WEBSOCKET_SERVER_URL = "ws://0.0.0.0:8080/";
    protected $pid;
    protected $started = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startWebSocketServer();
    }

    protected function tearDown(): void
    {
        if ($this->started) {
            exec("kill -9 {$this->pid}");
        }
        parent::tearDown();
    }

    protected function createWebSocketClient(): Client
    {
        $client = new Client(static::WEBSOCKET_SERVER_URL);
        $client
            ->addMiddleware(new CloseHandler())
            ->addMiddleware(new PingResponder());
        return $client;
    }

    protected function startWebSocketServer(): void
    {
        $cmd = "APP_ENV=testing php artisan websocket:serve > /dev/null 2>&1 & echo $!";

        $output = [];
        exec($cmd, $output);
        $this->pid = $output[0] ?? null;
        $this->started = true;

        sleep(3);
    }

    protected function authenticateClient(Client $client): void
    {
        $client->text(json_encode(['type' => 'auth', 'token' => 'token']));
        $client->receive();
    }

    protected function subscribeClient(Client $client, string $channel): void
    {
        $client->text(json_encode(['type' => 'subscribe', 'channel' => $channel]));
        $client->receive();
    }
}
