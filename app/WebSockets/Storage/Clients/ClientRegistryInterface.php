<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\Identity\ClientIdentity;
use Ratchet\ConnectionInterface;

interface ClientRegistryInterface
{
    public function register(ConnectionInterface $conn, ClientIdentity $identity): void;

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string;

    public function getIdentity(ConnectionInterface $conn): ?ClientIdentity;

    public function getIdentityByIdentifier(string $identifier): ?ClientIdentity;

    public function getConnectionsByIdentifier(string $identifier): array;

    public function forget(ConnectionInterface $conn): void;
}
