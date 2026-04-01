<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use Ratchet\ConnectionInterface;

interface WebSocketMessageSenderInterface
{
    public function sendToIdentifier(string $identifier, WebSocketMessage $message): void;

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void;
}
