<?php

namespace App\ApiClients;

interface SimpleDictionaryApiClientInterface
{
    public function validateToken(string $token): bool;

    public function getProfile(string $token): array;
}
