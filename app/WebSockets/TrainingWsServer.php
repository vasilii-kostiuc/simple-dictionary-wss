<?php

namespace App\WebSockets;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Handlers\Api\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Handlers\Internal\InternalMessageHandlerFactory;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class TrainingWsServer implements MessageComponentInterface
{
    protected array $clients = [];
    protected array $subscriptions = [];
    protected MessageBrokerInterface $messageBroker;

    public function __construct(
        protected readonly MessageHandlerFactory $messageHandlerFactory,
        protected readonly ApiMessageHandlerFactory $apiMessageHandlerFactory,
        protected readonly InternalMessageHandlerFactory $internalMessageHandlerFactory,
        protected readonly MessageBrokerFactory $messageBrokerFactory,
        protected readonly ClientsStorageInterface $storage,
        protected readonly TimerStorageInterface $timerStorage,
        protected readonly SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        protected readonly SubscriptionsStorageInterface $subscriptionsStorage,
        protected readonly LoopInterface $loop
    ) {
        Log::info(__METHOD__);

        $this->messageBroker = $this->messageBrokerFactory->create();
        $this->subscribeToApiMessages($this->messageBroker);
        $this->subscribeInternalMatchMakingMessages($this->messageBroker);
        $this->startExpiredTimersChecker();
    }

    private function subscribeInternalMatchMakingMessages(MessageBrokerInterface $messageBroker): void
    {
        $subscribeCallback = function ($message) {
            $data = json_decode($message, true);
            $type = $data['type'] ?? '';
            $handler = $this->internalMessageHandlerFactory->create($type);
            $handler->handle($data);
        };

        $messageBroker->subscribe('wss.matchmaking.joined', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.leaved', $subscribeCallback);
        $messageBroker->subscribe('wss.matchmaking.matched', $subscribeCallback);
    }

    private function subscribeToApiMessages(MessageBrokerInterface $messageBroker): void
    {

        $subscribeCallback = function ($message) {
            $data = json_decode($message, true);
            $type = $data['type'] ?? '';
            $handler = $this->apiMessageHandlerFactory->create($type);
            $handler->handle($data);
        };

        $messageBroker->subscribe('api.training', $subscribeCallback);
        $messageBroker->subscribe('api.match', $subscribeCallback);
    }

    /**
     * {@inheritDoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        Log::info('New connection ' . $conn->resourceId);
        $query = [];

        $this->clients[$conn->resourceId] = $conn;
    }

    /**
     * {@inheritDoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        Log::info(__METHOD__ . ' ' . $conn->resourceId);

        $userId = $this->storage->getUserIdByConnection($conn);

        if ($userId !== null) {
            $this->storage->remove($this->storage->getUserIdByConnection($conn), $conn);
        }

        $this->subscriptionsStorage->unsubscribeAll($conn);
        info(json_encode($this->subscriptionsStorage->getChannelsByConnection($conn)));
        Log::info(__METHOD__ . ' ' . $conn->resourceId);
    }

    /**
     * {@inheritDoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error(__METHOD__ . ' ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        Log::info(__METHOD__ . ' ' . $msg);
        Log::info(get_class($msg));

        $payload = json_decode($msg->getPayload(), false);

        if ($payload === null) {
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
                $type = $timer['type'];
                $entityId = $timer['entity_id'];

                Log::info('Completing expired timer', [
                    'type' => $type,
                    'entity_id' => $entityId,
                    'expired_at' => $timer['expires_at']->format('Y-m-d H:i:s'),
                ]);

                $this->simpleDictionaryApiClient->expire($entityId);
                $this->timerStorage->removeTimer($type, $entityId);
            }
        });

        Log::info('Expired timers checker started (interval: 30s)');
    }
}
