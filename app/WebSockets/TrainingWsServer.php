<?php

namespace App\WebSockets;

use App\WebSockets\ApiMessageHandlers\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\MessageHandlerFactory;
use App\WebSockets\Storage\ClientsStorageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class TrainingWsServer implements MessageComponentInterface
{
    protected array $clients = [];

    protected array $subscriptions = [];
    protected ClientsStorageInterface $storage;
    protected MessageHandlerFactory $messageHandlerFactory;
    protected ApiMessageHandlerFactory $apiMessageHandlerFactory;
    private MessageBrokerFactory $messageBrokerFactory;

    public function __construct(
        MessageHandlerFactory $messageHandlerFactory,
        ApiMessageHandlerFactory $apiMessageHandlerFactory,
        MessageBrokerFactory $messageBrokerFactory,
        ClientsStorageInterface $clientsStorage,
    ) {
        Log::info(__METHOD__);

        $this->storage = $clientsStorage;
        $this->messageHandlerFactory = $messageHandlerFactory;
        $this->apiMessageHandlerFactory = $apiMessageHandlerFactory;

        $this->messageBrokerFactory = $messageBrokerFactory;
        $messageBroker = $this->messageBrokerFactory ->create();
        $this->subscribeToApiMessages($messageBroker);
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

        $msgJson = json_decode($msg->getPayload());

        $handler = $this->messageHandlerFactory->create($msgJson->type ?? '');

        $handler->handle($conn, $msg);
    }
}
