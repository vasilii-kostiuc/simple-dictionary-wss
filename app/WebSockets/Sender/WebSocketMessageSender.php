<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class WebSocketMessageSender implements WebSocketMessageSenderInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly MessageBrokerInterface $messageBroker,
    ) {}

    public function sendToIdentifier(string $identifier, WebSocketMessage $message): void
    {
        foreach ($this->clientRegistry->getConnectionsByIdentifier($identifier) as $connection) {
            $connection->send($message);
        }
    }

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void
    {
        $identifier = $this->clientRegistry->getIdentifierByConnection($conn);
        if ($identifier !== null) {
            foreach ($this->clientRegistry->getConnectionsByIdentifier($identifier) as $connection) {
                $connection->send($message);
            }

            return;
        }

        $conn->send($message);
    }

    public function relayToIdentifier(string $identifier, WebSocketMessage $message): void
    {
        $this->messageBroker->publish('wss.relay.send', json_encode([
            'type' => 'wss.relay.send',
            'data' => [
                'identifier' => $identifier,
                'message' => [
                    'type' => $message->type,
                    'data' => $message->data,
                ],
            ],
        ]));
    }
}
