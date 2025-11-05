<?php

namespace App\WebSockets\Api\Fake;

use App\WebSockets\Api\SimpleDictionaryApiClientInterface;

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
