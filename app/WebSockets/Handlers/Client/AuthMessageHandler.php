<?php

namespace App\WebSockets\Handlers\Client;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
        private readonly ClientsStorageInterface $clientsStorage,
    ) {
    }

    public function handle(ConnectionInterface $conn, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $token = $data['token'] ?? null;

        if (! $token) {
            $conn->send(new ErrorMessage('token_required', []));

            return;
        }

        $userData = $this->apiClient->getUserByToken($token);

        if (! $userData) {
            $conn->send(new ErrorMessage('invalid_token', []));

            return;
        }

        $this->clientsStorage->add($conn, $userData);
        $conn->send(new WebSocketMessage('auth_success', ['userId' => $userData->id]));
    }
}
