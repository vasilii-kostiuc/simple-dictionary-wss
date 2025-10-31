<?php

namespace App\Console\Commands;

use App\WebSockets\Handlers\MessageHandlerFactory;
use App\WebSockets\TrainingWsServer;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebsocketServerRunCommand extends Command
{
    private MessageHandlerFactory $messageHandlerFactory;

    public function __construct(MessageHandlerFactory $messageHandlerFactory)
    {
        parent::__construct();
        $this->messageHandlerFactory = $messageHandlerFactory;
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
    public function handle()
    {
        $loop = Loop::get();

        $trainingWsServer = new TrainingWsServer($this->messageHandlerFactory);

        $wsServer = new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($trainingWsServer)),
            new SocketServer('0.0.0.0:8080', [], $loop),
            $loop
        );

        $loop->run();
    }
}
