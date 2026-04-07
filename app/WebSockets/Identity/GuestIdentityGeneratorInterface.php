<?php

namespace App\WebSockets\Identity;

interface GuestIdentityGeneratorInterface
{
    public function generateName(): string;

    public function generateAvatar(string $guestId): string;
}
