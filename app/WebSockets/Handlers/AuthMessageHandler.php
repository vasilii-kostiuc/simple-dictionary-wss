<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\AuthorizedClientsStorage;
use App\WebSockets\Storage\ClientsStorageInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthMessageHandler implements MessageHandlerInterface
{
    protected ClientInterface $client;
    private ClientsStorageInterface $clientsStorage;

    public function __construct(ClientInterface $client, ClientsStorageInterface $clientsStorage)
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
        if (!$this->validateToken($token) && $userId = $this->getUserIdFromToken($token)) {
            $this->clientsStorage->add($userId, $from);
            $from->send(new WebSocketMessage('auth', ['success' => false, 'message' => 'auth error']));
            $from->close();
        } else {
            $from->send(new WebSocketMessage('auth', ['success' => false, 'message' => 'auth error']));
        }
    }

    private function validateToken(string $token): bool
    {
        try {
            $validateResponse = $this->client->get('auth/token/validate', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
        } catch (GuzzleException $e) {
            return false;
        }

        if ($validateResponse->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    private function getUserIdFromToken(string $token): ?int
    {
        try {
            $userResponse = $this->client->get('profile', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
        } catch (GuzzleException $e) {
            return null;
        }

        if ($userResponse->getStatusCode() === 200) {
            return $userResponse->getBody()['data']['id'];
        }

        return null;
    }
}
