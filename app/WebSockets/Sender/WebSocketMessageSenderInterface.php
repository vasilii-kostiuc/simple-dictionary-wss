<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use Ratchet\ConnectionInterface;

interface WebSocketMessageSenderInterface
{
    public function sendToUser(int $userId, WebSocketMessage $message): void;

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void;
}
