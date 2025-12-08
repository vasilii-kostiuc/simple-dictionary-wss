<?php

namespace App\Console\Commands;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\ApiMessageHandlers\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\MessageHandlerFactory;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\TrainingWsServer;
use App\WebSockets\Storage\Timers\TrainingTimerStorageInterface;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;

class WebsocketServerRunCommand extends Command
{
    private MessageHandlerFactory $messageHandlerFactory;
    private MessageBrokerFactory $messageBrokerFactory;
    private ClientsStorageInterface $clientsStorage;
    private ApiMessageHandlerFactory $apiMessageHandlerFactory;
    private TrainingTimerStorageInterface $timerStorage;
    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(
        MessageHandlerFactory $messageHandlerFactory,
        MessageBrokerFactory $messageBrokerFactory,
        ClientsStorageInterface $clientsStorage,
        SubscriptionsStorageInterface $subscriptionsStorage,
        TrainingTimerStorageInterface $timerStorage,
        SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
    ) {
        parent::__construct();
        $this->messageHandlerFactory = $messageHandlerFactory;
        $this->messageBrokerFactory = $messageBrokerFactory;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->timerStorage = $timerStorage;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
        $this->apiMessageHandlerFactory = new ApiMessageHandlerFactory($this->subscriptionsStorage, Loop::get(), $this->simpleDictionaryApiClient, $this->timerStorage, );

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

        $trainingWsServer = new TrainingWsServer(
            $this->messageHandlerFactory,
            $this->apiMessageHandlerFactory,
            $this->messageBrokerFactory,
            $this->clientsStorage,
            $this->timerStorage,
            $this->simpleDictionaryApiClient,
            $loop
        );

        new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($trainingWsServer)),
            new SocketServer('0.0.0.0:8080', [], $loop),
            $loop
        );

        $loop->run();
    }
}
