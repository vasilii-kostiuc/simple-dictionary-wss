<?php

namespace App\WebSockets\Storage\MatchMaking;

use App\WebSockets\DTO\UserData;

interface MatchMakingQueueInterface
{
    public function add(UserData $userData, array $matchParams): void;

    public function remove(int $userId, array $matchParams = []): void;

    public function all(array $matchParams): array;

    public function allQueues(): array;

    public function findMatch(int $userId, array $matchParams): ?int;

    public function clear(array $matchParams): void;

    public function count(array $matchParams): int;
}
