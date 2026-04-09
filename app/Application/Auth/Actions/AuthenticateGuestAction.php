<?php

namespace App\Application\Auth\Actions;

use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\ClientIdentityLookupInterface;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;

class AuthenticateGuestAction
{
    public function __construct(
        private readonly ClientIdentityLookupInterface $clientIdentityLookup,
        private readonly GuestIdentityFactoryInterface $guestIdentityFactory,
    ) {
    }

    public function execute(?string $guestId): ClientIdentity
    {
        if ($guestId !== null) {
            $existingIdentity = $this->clientIdentityLookup->findByIdentifier($guestId);

            if ($existingIdentity !== null) {
                return $existingIdentity;
            }
        }

        return $this->guestIdentityFactory->create($guestId);
    }
}
