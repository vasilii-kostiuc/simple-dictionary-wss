<?php

namespace App\WebSockets\Handlers;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\ClientsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthMessageHandler implements MessageHandlerInterface
{
    protected SimpleDictionaryApiClientInterface $client;
    private ClientsStorageInterface $clientsStorage;

    public function __construct(SimpleDictionaryApiClientInterface $client, ClientsStorageInterface $clientsStorage)
    {
        $this->client = $client;
        $this->clientsStorage = $clientsStorage;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        info(__METHOD__);
        info($msg);

        $msgJson = json_decode($msg->getPayload());
        $token = $msgJson->token ?? "";
        if ($this->validateToken($token) && $userId = $this->getUserIdFromToken($token)) {
            $this->clientsStorage->add($userId, $from);
            $from->send(new WebSocketMessage('auth_success', ['success' => true, 'message' => 'auth successed']));
        } else {
            $from->send(new ErrorMessage('auth_error', $msg->getPayload()));
        }
    }

    private function validateToken(string $token): bool
    {
        return $this->client->validateToken($token);
    }

    private function getUserIdFromToken(string $token): ?int
    {
        $profile = $this->client->getProfile($token);

        return $profile['id'] ?? null;
    }
}
