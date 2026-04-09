<?php

namespace App\Infrastructure\Identity;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;

class SimpleDictionaryApiUserIdentityResolver implements UserIdentityResolverInterface
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function resolveByToken(string $token): ?ClientIdentity
    {
        $response = $this->apiClient->validateToken($token);

        if (empty($response)) {
            return null;
        }

        return new ClientIdentity(
            id: $response['id'],
            name: $response['name'] ?? '',
            email: $response['email'] ?? '',
            avatar: $response['avatar'] ?? null,
        );
    }
}
