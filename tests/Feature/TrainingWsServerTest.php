<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TrainingWsServerTest extends TestCase
{
    const string WEBSOCKET_SERVER_URL = "ws://0.0.0.0:8080/";
    private $pid;

    private $started = false;

    public function initializeWebSocketClient(): \WebSocket\Client
    {
        $client = new WebSocket\Client(self::WEBSOCKET_SERVER_URL);
        $client
            ->addMiddleware(new WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new WebSocket\Middleware\PingResponder());
        return $client;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $output = $this->startWebSocketServer();
    }

    public function tearDown(): void
    {
        if ($this->started) {
            exec("kill -9 {$this->pid}");
        }
        parent::tearDown();
    }

    public function test_training_ws_server_is_running(): void
    {
        $this->assertIsNumeric($this->pid);
        $this->assertTrue($this->started);
    }

    public function test_connect_to_ws_training_server(): void
    {
        Log::info('test log');

        $client = $this->initializeWebSocketClient();

        $client->text("Hello WebSocket.org!");
        $message = $client->receive();

        $content = $message->getContent();
        $this->assertNotEmpty($content);
        $client->close();
    }

    public function test_auth_message()
    {
        Redis::subscribe(['training'], function () {});

        $client = $this->initializeWebSocketClient();

        $client->text(json_encode(['type' => 'auth', 'token' => 'token']));

        $message = $client->receive();

        $messageType = json_decode($message->getPayload())->type??null;
        $this->assertEquals($messageType, 'auth_success');
        info('message: ' . $message->getContent() . '');
        $client->close();
    }

    public function test_subscribe_message()
    {
        $client = $this->initializeWebSocketClient();

        $client->text(json_encode(['type' => 'auth', 'token' => 'token']));

        $message = $client->receive();
        $messageType = json_decode($message->getPayload())->type??null;
        $this->assertEquals('auth_success', $messageType);

        $client->text(json_encode(['type' => 'subscribe', 'channel' => 'trainings.121']));

        $message = $client->receive();
        info('message: ' . $message->getContent() . '');;
        $messageType = json_decode($message->getPayload())->type??null;
        $this->assertEquals('subscribe_success', $messageType);
    }

    protected function startWebSocketServer(): array
    {
        $cmd = "APP_ENV=testing php artisan websocket:serve > /dev/null 2>&1 & echo $!";

        $output = [];
        exec($cmd, $output);
        sleep(2);
        $this->pid = $output[0] ?? null;

        $this->started = true;
        return $output;
    }
}
