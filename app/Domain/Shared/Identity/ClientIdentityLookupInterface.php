<?php

namespace App\Domain\Shared\Identity;

interface ClientIdentityLookupInterface
{
    public function findByIdentifier(string $identifier): ?ClientIdentity;
}
