<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TrainingWsServerTest extends TestCase
{
    const WEBSOCKET_SERVER_URL = "ws://0.0.0.0:8080/";
    private $pid;

    private $started = false;

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


        $client = new WebSocket\Client(self::WEBSOCKET_SERVER_URL);
        $client
            // Add standard middlewares
            ->addMiddleware(new WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new WebSocket\Middleware\PingResponder());

        $client->text("Hello WebSocket.org!");

        $message = $client->receive();
        $content = $message->getContent();
        $this->assertNotEmpty($content);
        $client->close();
    }

    public function test_auth_message()
    {
        $client = new WebSocket\Client(self::WEBSOCKET_SERVER_URL);
        $client
            // Add standard middlewares
            ->addMiddleware(new WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new WebSocket\Middleware\PingResponder());

        $client->text(json_encode(['type' => 'auth', 'token' => 'token']));

        $message = $client->receive();

        $messageType = json_decode($message->getPayload())->type??null;
        $this->assertEquals($messageType, 'auth_success');
        info('message: ' . $message->getContent() . '');
        $client->close();
    }

    public function test_subscribe_message()
    {
        $client = new WebSocket\Client(self::WEBSOCKET_SERVER_URL);
        $client
            // Add standard middlewares
            ->addMiddleware(new WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new WebSocket\Middleware\PingResponder());

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

    /**
     * @return array
     */
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
