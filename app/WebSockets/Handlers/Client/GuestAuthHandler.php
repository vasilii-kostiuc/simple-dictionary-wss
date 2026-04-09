<?php

namespace App\WebSockets\Handlers\Client;

use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class GuestAuthHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly GuestIdentityFactoryInterface $guestIdentityFactory,
    ) {
    }

    public function handle(ConnectionInterface $conn, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];

        $guestId = $data['guest_id'] ?? null;
        if ($guestId !== null && ! preg_match('/^[0-9a-f\-]{36}$/i', $guestId)) {
            $conn->send(new ErrorMessage('invalid_guest_id', []));

            return;
        }

        $identity = $this->guestIdentityFactory->create($guestId);

        $this->clientRegistry->register($conn, $identity);

        $conn->send(new WebSocketMessage('guest_auth_success', $identity->toArray()));
    }
}
