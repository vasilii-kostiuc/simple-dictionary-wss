<?php

namespace App\Application\Contracts;

use App\Domain\Shared\DTO\ConnectedUser;

interface SimpleDictionaryApiClientInterface
{
    public function getUserByToken(string $token): ?ConnectedUser;

    public function expire(string|int $trainingId): array;

    public function expireMatch(string|int $matchId): array;

    public function createMatch(array $participants, array $matchParams): array;
}
