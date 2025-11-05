<?php

namespace App\Console\Commands;

use App\WebSockets\Handlers\MessageHandlerFactory;
use App\WebSockets\Storage\ClientsStorageInterface;
use App\WebSockets\TrainingWsServer;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;

class WebsocketServerRunCommand extends Command
{
    private MessageHandlerFactory $messageHandlerFactory;
    private MessageBrokerFactory $messageBrokerFactory;
    private ClientsStorageInterface $clientsStorage;

    public function __construct(MessageHandlerFactory $messageHandlerFactory, MessageBrokerFactory $messageBrokerFactory, ClientsStorageInterface $clientsStorage)
    {
        parent::__construct();
        $this->messageHandlerFactory = $messageHandlerFactory;
        $this->messageBrokerFactory = $messageBrokerFactory;
        $this->clientsStorage = $clientsStorage;
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

        $trainingWsServer = new TrainingWsServer($this->messageHandlerFactory, $this->messageBrokerFactory, $this->clientsStorage);

        $wsServer = new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($trainingWsServer)),
            new SocketServer('0.0.0.0:8080', [], $loop),
            $loop
        );

        $loop->run();
    }
}
