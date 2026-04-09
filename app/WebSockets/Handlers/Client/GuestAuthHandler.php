<?php

namespace App\WebSockets\Handlers\Client;

use App\Domain\Shared\DTO\ConnectedUser;
use App\WebSockets\Identity\GuestIdentityGeneratorInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class GuestAuthHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly GuestIdentityGeneratorInterface $identityGenerator,
    ) {
    }

    public function handle(ConnectionInterface $conn, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];

        $guestId = $data['guest_id'] ?? null;
        $name = trim($data['name'] ?? '');

        if ($guestId !== null && ! preg_match('/^[0-9a-f\-]{36}$/i', $guestId)) {
            $conn->send(new ErrorMessage('invalid_guest_id', []));

            return;
        }

        if (! $guestId) {
            $guestId = (string) Str::uuid();
        }


        $name = $this->identityGenerator->generateName();
        $avatar = $this->identityGenerator->generateAvatar($guestId);

        $userData = new ConnectedUser(
            id: null,
            name: $name,
            email: '',
            avatar: $avatar,
            guestId: $guestId,
        );

        $this->clientsStorage->add($conn, $userData);

        $conn->send(new WebSocketMessage('guest_auth_success', $userData->toArray()));
    }
}
