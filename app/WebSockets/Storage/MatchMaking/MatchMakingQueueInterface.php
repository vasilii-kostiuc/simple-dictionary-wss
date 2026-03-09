<?php

namespace App\WebSockets\Storage\MatchMaking;

interface MatchMakingQueueInterface
{

    public function add(string $userId, array $matchParams): void;

    public function remove(string $userId, array $matchParams): void;

    public function all(array $matchParams): array;

    public function findMatch(string $userId, array $matchParams): ?string;

    public function clear(array $matchParams): void;

    public function count(array $matchParams): int;
}
