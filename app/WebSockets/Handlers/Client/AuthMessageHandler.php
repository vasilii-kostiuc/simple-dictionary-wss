<?php

namespace App\WebSockets\Handlers\Client;

use App\Domain\Shared\Identity\UserIdentityResolverInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly UserIdentityResolverInterface $userIdentityResolver,
        private readonly ClientRegistryInterface $clientRegistry,
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

        $identity = $this->userIdentityResolver->resolveByToken($token);

        if (! $identity) {
            $conn->send(new ErrorMessage('invalid_token', []));

            return;
        }

        $this->clientRegistry->register($conn, $identity);
        $conn->send(new WebSocketMessage('auth_success', ['userId' => $identity->id]));
    }
}
