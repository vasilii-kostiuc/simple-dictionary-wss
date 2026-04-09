<?php

namespace App\Domain\Shared\Identity;

interface UserIdentityResolverInterface
{
    public function resolveByToken(string $token): ?ClientIdentity;
}
