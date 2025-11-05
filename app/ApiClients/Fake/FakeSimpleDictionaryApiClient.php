<?php

namespace App\ApiClients\Fake;

use App\ApiClients\SimpleDictionaryApiClientInterface;

class FakeSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{

    public function validateToken(string $token): bool
    {
        return true;
    }

    public function getProfile(string $token): array
    {
        return ['id' => 42];
    }
}
