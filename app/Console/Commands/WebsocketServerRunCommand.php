<?php

namespace App\Console\Commands;

use App\WebSockets\TrainingWsServer;
use Illuminate\Console\Command;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;

class WebsocketServerRunCommand extends Command
{
    public function __construct(
        private readonly TrainingWsServer $trainingWsServer,
        private readonly LoopInterface $loop,
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run websocket server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->trainingWsServer->boot();

        new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($this->trainingWsServer)),
            new SocketServer('0.0.0.0:8080', [], $this->loop),
            $this->loop
        );

        $this->loop->run();

        return self::SUCCESS;
    }
}
