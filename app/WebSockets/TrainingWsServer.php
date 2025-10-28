<?php

namespace App\WebSockets;

use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;


class TrainingWsServer implements MessageComponentInterface
{

    protected array $clients = [];

    protected array $subscriptions = [];

    public function __construct()
    {
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
        parse_str(parse_url($conn->httpRequest->getUri(), PHP_URL_QUERY), $query);
        $token = $query['token'] ?? "";

        if (!$this->validateToken($token)) {
            $conn->close();
            return;
        }

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

    }

    protected function validateToken(string $token): bool
    {
        return true;
    }
}
