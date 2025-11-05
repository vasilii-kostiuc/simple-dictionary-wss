<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TrainingWsServerTest extends TestCase
{
    private $pid;

    private $started = false;

    public function setUp(): void
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


        $client = new WebSocket\Client("ws://0.0.0.0:8080/");
        $client
            // Add standard middlewares
            ->addMiddleware(new WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new WebSocket\Middleware\PingResponder());

// Send a message
        $client->text("Hello WebSocket.org!");

// Read response (this is blocking)
        $message = $client->receive();
        $content = $message->getContent();
        $this->assertNotEmpty($content);
        info('message: ' . $content . '');
        $client->close();
    }

    public function test_auth_message()
    {
        $client = new WebSocket\Client("ws://0.0.0.0:8080/");
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
