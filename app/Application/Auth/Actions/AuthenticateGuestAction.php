<?php

namespace App\Application\Auth\Actions;

use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;

class AuthenticateGuestAction
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly GuestIdentityFactoryInterface $guestIdentityFactory,
    ) {
    }

    public function execute(?string $guestId): ClientIdentity
    {
        if ($guestId !== null) {
            $existingIdentity = $this->clientRegistry->getIdentityByIdentifier($guestId);

            if ($existingIdentity !== null) {
                return $existingIdentity;
            }
        }

        return $this->guestIdentityFactory->create($guestId);
    }
}
