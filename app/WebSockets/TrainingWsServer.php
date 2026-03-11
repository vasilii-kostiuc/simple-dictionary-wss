<?php

namespace App\WebSockets;

use App\WebSockets\Handlers\Api\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Handlers\Internal\InternalMessageHandlerFactory;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;
use App\WebSockets\Storage\Timers\TrainingTimerStorageInterface;
use App\ApiClients\SimpleDictionaryApiClientInterface;


class TrainingWsServer implements MessageComponentInterface
{
    protected array $clients = [];

    protected array $subscriptions = [];
    protected ClientsStorageInterface $storage;
    protected MessageHandlerFactory $messageHandlerFactory;
    protected ApiMessageHandlerFactory $apiMessageHandlerFactory;
    protected InternalMessageHandlerFactory $internalMessageHandlerFactory;
    private MessageBrokerFactory $messageBrokerFactory;
    private $loop;
    private MessageBrokerInterface $messageBroker;
    private TrainingTimerStorageInterface $timerStorage;
    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(
        MessageHandlerFactory $messageHandlerFactory,
        ApiMessageHandlerFactory $apiMessageHandlerFactory,
        InternalMessageHandlerFactory $internalMessageHandlerFactory,
        MessageBrokerFactory $messageBrokerFactory,
        ClientsStorageInterface $clientsStorage,
        TrainingTimerStorageInterface $timerStorage,
        SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        SubscriptionsStorageInterface $subscriptionsStorage,
        $loop
    ) {
        Log::info(__METHOD__);

        $this->storage = $clientsStorage;
        $this->messageHandlerFactory = $messageHandlerFactory;
        $this->apiMessageHandlerFactory = $apiMessageHandlerFactory;
        $this->internalMessageHandlerFactory = $internalMessageHandlerFactory;
        $this->loop = $loop;
        $this->timerStorage = $timerStorage;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;

        $this->messageBrokerFactory = $messageBrokerFactory;
        $this->messageBroker = $this->messageBrokerFactory->create();
        $this->subscribeToApiMessages($this->messageBroker);

        $this->subscribeInternalMatchMakingMessages($this->messageBroker);

        $this->startExpiredTimersChecker();
        $this->subscriptionsStorage = $subscriptionsStorage;
    }


    private function subscribeInternalMatchMakingMessages(MessageBrokerInterface $messageBroker): void
    {
        Log::info(__METHOD__);


        $subscribeCallback =function ($message) {

            Log::info("Internal matchmaking message received: " . $message);

            $data = json_decode($message, true);
            $type = $data['type'] ?? '';

            $handler = $this->internalMessageHandlerFactory->create($type);
            $handler->handle('matchmaking', $data);
        };

        $messageBroker->subscribe('wss.matchmaking.joined', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.left', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.matched', $subscribeCallback);
    }
    
    private function subscribeToApiMessages(MessageBrokerInterface $messageBroker): void
    {
        Log::info(__METHOD__);
        $messageBroker->subscribe('training', function ($message) {

            Log::info("API message received: " . $message);

            $data = json_decode($message, true);
            $type = $data['type'] ?? '';

            $handler = $this->apiMessageHandlerFactory->create($type);
            $handler->handle('training', $data);
        });

    }

    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        Log::info('New connection ' . $conn->resourceId);
        $query = [];

        $this->clients[$conn->resourceId] = $conn;
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $conn)
    {
        Log::info(__METHOD__ . ' ' . $conn->resourceId);

        $userId = $this->storage->getUserIdByConnection($conn);

        if($userId !== null) {
            $this->storage->remove($this->storage->getUserIdByConnection($conn), $conn);
        }

        $this->subscriptionsStorage->unsubscribeAll($conn);
        info(json_encode($this->subscriptionsStorage->getChannelsByConnection($conn)));
        Log::info(__METHOD__ . ' ' . $conn->resourceId);
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error(__METHOD__ . ' ' . $e->getMessage());
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        Log::info(__METHOD__ . ' ' . $msg);
        Log::info(get_class($msg));

        $payload = json_decode($msg->getPayload(), false);

        if($payload === null) {
            Log::warning('Invalid JSON received: ' . $msg->getPayload());
            $conn->send(new ErrorMessage('invalid_json', $msg->getPayload()));
            return;
        }

        $handler = $this->messageHandlerFactory->create($payload->type ?? '', $payload);

        $handler->handle($conn, $msg);
    }

    private function startExpiredTimersChecker(): void
    {
        $this->loop->addPeriodicTimer(5, function () {
            Log::info('Checking for expired training timers');

            $expiredTimers = $this->timerStorage->getExpiredTimers();

            if (empty($expiredTimers)) {
                return;
            }

            Log::info('Found expired timers', ['count' => count($expiredTimers)]);

            foreach ($expiredTimers as $timer) {
                $trainingId = $timer['training_id'];

                Log::info("Completing expired training", [
                    'training_id' => $trainingId,
                    'expired_at' => $timer['expires_at']->format('Y-m-d H:i:s')
                ]);

                $this->simpleDictionaryApiClient->expire($trainingId);
                $this->timerStorage->removeTimer($trainingId);
            }
        });

        Log::info('Expired timers checker started (interval: 30s)');
    }

}
