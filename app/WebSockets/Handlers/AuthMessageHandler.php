<?php

namespace App\WebSockets\Handlers;

use GuzzleHttp\ClientInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthMessageHandler implements MessageHandlerInterface
{
    protected ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg)
    {
        info(__METHOD__);
        info($msg);

        $msgJson = json_decode($msg->getPayload());
        $token = $msgJson->token ?? "";
        if (!$this->validateToken($token)) {
            $from->send(json_encode([
                'type' => 'auth',
                'data' => ['success' => false, 'message' => 'Auth error']
            ]));
            $from->close();
        } else {
            $from->send(json_encode([
                'type' => 'auth',
                'data' => ['success' => true, 'message' => 'Auth success']
            ]));
        }
    }

    private function validateToken(string $token): bool
    {
        $validateResponse = $this->client->get('auth/token/validate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);

        if ($validateResponse->getStatusCode() === 200) {
            return true;
        }

        return false;
    }
}
