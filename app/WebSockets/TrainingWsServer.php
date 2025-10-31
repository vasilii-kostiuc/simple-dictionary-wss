<?php

namespace App\WebSockets;

use App\WebSockets\Handlers\MessageHandlerFactory;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;


class TrainingWsServer implements MessageComponentInterface
{
    protected array $clients = [];

    protected array $subscriptions = [];
    private MessageHandlerFactory $messageHandlerFactory;

    public function __construct(MessageHandlerFactory $messageHandlerFactory)
    {
        $this->messageHandlerFactory = $messageHandlerFactory;
        Log::info(__METHOD__);
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
