<?php

namespace App\Application\Auth\Actions;

use App\Application\Auth\Exceptions\AuthException;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;

class AuthenticateUserAction
{
    public function __construct(
        private readonly UserIdentityResolverInterface $userIdentityResolver,
    ) {
    }

    /**
     * @throws AuthException
     */
    public function execute(string $token): ClientIdentity
    {
        $identity = $this->userIdentityResolver->resolveByToken($token);

        if ($identity === null) {
            throw new AuthException('invalid_token');
        }

        return $identity;
    }
}
