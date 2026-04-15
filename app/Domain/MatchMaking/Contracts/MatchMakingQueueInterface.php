<?php

namespace App\Domain\MatchMaking\Contracts;

use App\Domain\Match\MatchParams;
use App\Domain\MatchMaking\QueueEntry;
use App\Domain\Shared\Identity\ClientIdentity;

interface MatchMakingQueueInterface
{
    public function add(ClientIdentity $identity, MatchParams $matchParams): void;

    public function remove(string $identifier): void;

    /** @return QueueEntry[] */
    public function all(MatchParams $matchParams): array;

    /** @return QueueEntry[] */
    public function allQueues(): array;

    public function findMatch(string $identifier, MatchParams $matchParams): ?string;

    public function clear(MatchParams $matchParams): void;

    public function count(MatchParams $matchParams): int;

    public function isUserInQueue(string $identifier): bool;

    public function extract(string $identifier): ?QueueEntry;
}
