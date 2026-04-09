<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\Identity\ClientIdentity;
use Ratchet\ConnectionInterface;

class GuestClientRegistry implements ClientRegistryInterface
{
    private array $clients = [];

    public function register(ConnectionInterface $conn, ClientIdentity $identity): void
    {
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'identity' => $identity,
        ];
    }

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string
    {
        return $this->clients[$conn->resourceId]['identity']?->guestId ?? null;
    }

    public function getIdentity(ConnectionInterface $conn): ?ClientIdentity
    {
        return $this->clients[$conn->resourceId]['identity'] ?? null;
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = [];
        foreach ($this->clients as $client) {
            if ($client['identity']->guestId === $identifier) {
                $connections[] = $client['connection'];
            }
        }

        return $connections;
    }

    public function forget(ConnectionInterface $conn): void
    {
        unset($this->clients[$conn->resourceId]);
    }
}
