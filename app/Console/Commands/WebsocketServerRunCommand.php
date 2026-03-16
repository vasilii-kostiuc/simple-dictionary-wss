<?php

namespace App\Console\Commands;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Handlers\Api\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Handlers\Internal\InternalMessageHandlerFactory;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
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

    private ApiMessageHandlerFactory $apiMessageHandlerFactory;

    private TimerStorageInterface $timerStorage;

    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    private SubscriptionsStorageInterface $subscriptionsStorage;

    private MatchMakingQueueInterface $matchMakingQueue;

    private InternalMessageHandlerFactory $internalMessageHandlerFactory;

    public function __construct(
        MessageHandlerFactory $messageHandlerFactory,
        MessageBrokerFactory $messageBrokerFactory,
        ClientsStorageInterface $clientsStorage,
        SubscriptionsStorageInterface $subscriptionsStorage,
        TimerStorageInterface $timerStorage,
        SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        MatchMakingQueueInterface $matchMakingQueue,
        InternalMessageHandlerFactory $internalMessageHandlerFactory
    ) {
        parent::__construct();
        $this->messageHandlerFactory = $messageHandlerFactory;
        $this->messageBrokerFactory = $messageBrokerFactory;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->timerStorage = $timerStorage;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
        $this->matchMakingQueue = $matchMakingQueue;
        $this->apiMessageHandlerFactory = new ApiMessageHandlerFactory($this->subscriptionsStorage, Loop::get(), $this->simpleDictionaryApiClient, $this->timerStorage);
        $this->internalMessageHandlerFactory = $internalMessageHandlerFactory;
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
            $this->internalMessageHandlerFactory,
            $this->messageBrokerFactory,
            $this->clientsStorage,
            $this->timerStorage,
            $this->simpleDictionaryApiClient,
            $this->subscriptionsStorage,
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
