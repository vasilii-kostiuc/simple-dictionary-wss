<?php

namespace App\WebSockets;

use App\WebSockets\Handlers\MessageHandlerFactory;
use App\WebSockets\Storage\ClientsStorageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;

class TrainingWsServer implements MessageComponentInterface
{
    protected array $clients = [];

    protected array $subscriptions = [];
    private MessageHandlerFactory $messageHandlerFactory;

    public function __construct(MessageHandlerFactory $messageHandlerFactory, MessageBrokerFactory $messageBrokerFactory, ClientsStorageInterface $clientsStorage)
    {
        Log::info(__METHOD__);

        $this->storage = $clientsStorage;

        $this->messageHandlerFactory = $messageHandlerFactory;

        $messageBroker = $messageBrokerFactory->create();
//        try {
//        Redis::subscribe(['training'], function () {});
//        }catch (\Exception $e){
//            info($e->getMessage());
//        }
        info(__METHOD__);
        info($messageBroker::class);
//        $messageBroker->subscribe('training', function ($message) {
//            info("Broker Message : " . json_decode($message));
//        });
    }

    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        // Получаем токен из query
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
