<?php

namespace App\ApiClients;

use App\WebSockets\DTO\UserData;

interface SimpleDictionaryApiClientInterface
{
    public function getUserByToken(string $token): ?UserData;

    public function expire(string|int $trainingId): array;
}
