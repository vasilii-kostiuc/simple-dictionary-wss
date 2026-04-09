<?php

namespace App\Domain\MatchMaking\Contracts;

use App\Domain\Shared\DTO\ConnectedUser;

interface MatchMakingQueueInterface
{
    public function add(ConnectedUser $userData, array $matchParams): void;

    public function remove(string $identifier, array $matchParams = []): void;

    public function all(array $matchParams): array;

    public function allQueues(): array;

    public function findMatch(string $identifier, array $matchParams): ?string;

    public function clear(array $matchParams): void;

    public function count(array $matchParams): int;

    public function isUserInQueue(string $identifier): bool;

    public function extract(string $identifier): ?array;
}
