<?php

namespace App\Application\Contracts;

interface SimpleDictionaryApiClientInterface
{
    public function validateToken(string $token): array;

    public function expire(string|int $trainingId): array;

    public function expireMatch(string|int $matchId): array;

    public function createMatch(array $participants, array $matchParams): array;
}
