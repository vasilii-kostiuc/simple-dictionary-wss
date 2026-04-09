<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\Identity\ClientIdentity;
use Ratchet\ConnectionInterface;

class CompositeClientRegistry implements ClientRegistryInterface
{
    public function __construct(
        private readonly AuthorizedClientRegistry $authorizedRegistry,
        private readonly GuestClientRegistry $guestRegistry,
    ) {
    }

    public function register(ConnectionInterface $conn, ClientIdentity $identity): void
    {
        if ($identity->isGuest()) {
            $this->guestRegistry->register($conn, $identity);
        } else {
            $this->authorizedRegistry->register($conn, $identity);
        }
    }

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string
    {
        return $this->authorizedRegistry->getIdentifierByConnection($conn)
            ?? $this->guestRegistry->getIdentifierByConnection($conn);
    }

    public function getIdentity(ConnectionInterface $conn): ?ClientIdentity
    {
        return $this->authorizedRegistry->getIdentity($conn)
            ?? $this->guestRegistry->getIdentity($conn);
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = $this->authorizedRegistry->getConnectionsByIdentifier($identifier);

        return $connections ?: $this->guestRegistry->getConnectionsByIdentifier($identifier);
    }

    public function forget(ConnectionInterface $conn): void
    {
        $this->authorizedRegistry->forget($conn);
        $this->guestRegistry->forget($conn);
    }
}
