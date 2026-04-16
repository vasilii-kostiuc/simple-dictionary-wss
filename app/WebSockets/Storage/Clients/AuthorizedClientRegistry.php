<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\ClientIdentityLookupInterface;
use Ratchet\ConnectionInterface;

class AuthorizedClientRegistry implements ClientIdentityLookupInterface, ClientRegistryInterface
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
        $identity = $this->clients[$conn->resourceId]['identity'] ?? null;

        return $identity !== null ? $identity->getIdentifier() : null;
    }

    public function getIdentity(ConnectionInterface $conn): ?ClientIdentity
    {
        return $this->clients[$conn->resourceId]['identity'] ?? null;
    }

    public function findByIdentifier(string $identifier): ?ClientIdentity
    {
        foreach ($this->clients as $client) {
            if ($client['identity']->getIdentifier() === $identifier) {
                return $client['identity'];
            }
        }

        return null;
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = [];
        foreach ($this->clients as $client) {
            if ($client['identity']->getIdentifier() === $identifier) {
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
