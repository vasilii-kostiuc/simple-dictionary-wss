<?php

namespace App\Application\Contracts;

use App\Domain\LinkMatch\LinkMatch;

interface SimpleDictionaryApiClientInterface
{
    public function validateToken(string $token): array;

    public function expire(string|int $trainingId): array;

    public function expireMatch(string|int $matchId): array;

    public function createMatch(array $participants, array $matchParams): array;

    public function getLinkMatch(string $token): ?LinkMatch;
}
