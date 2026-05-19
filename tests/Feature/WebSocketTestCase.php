<?php

namespace Tests\Feature;

use Tests\TestCase;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

abstract class WebSocketTestCase extends TestCase
{
    protected const string WEBSOCKET_SERVER_URL = 'ws://0.0.0.0:8080/';

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
            ->addMiddleware(new CloseHandler)
            ->addMiddleware(new PingResponder);

        return $client;
    }

    protected function startWebSocketServer(): void
    {
        $cmd = 'APP_ENV=testing php artisan websocket:serve > /dev/null 2>&1 & echo $!';

        $output = [];
        exec($cmd, $output);
        $this->pid = $output[0] ?? null;
        $this->started = true;

        $this->waitForPort('0.0.0.0', 8080);
    }

    private function waitForPort(string $host, int $port, int $timeoutSeconds = 10): void
    {
        $start = time();

        while (time() - $start < $timeoutSeconds) {
            $socket = @fsockopen($host, $port, $errno, $errstr, 0.1);

            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(100_000);
        }

        $this->fail("WebSocket server did not start on {$host}:{$port} within {$timeoutSeconds}s");
    }

    protected function authenticateClient(Client $client, string $token = 'token'): void
    {
        $client->text(json_encode(['type' => 'auth', 'data' => ['token' => $token]]));
        $client->receive();
    }

    protected function subscribeClient(Client $client, string $channel): void
    {
        $client->text(json_encode(['type' => 'subscribe', 'data' => ['channel' => $channel]]));
        $client->receive();
    }
}
