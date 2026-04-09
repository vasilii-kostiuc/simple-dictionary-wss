<?php

namespace App\Domain\Shared\Identity;

interface GuestIdentityFactoryInterface
{
    public function create(?string $guestId = null): ClientIdentity;
}
