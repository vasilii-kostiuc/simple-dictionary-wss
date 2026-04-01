<?php

namespace App\WebSockets\DTO;

class UserData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar,
        public readonly ?string $guestId = null,
    ) {
    }

    public function isGuest(): bool
    {
        return $this->guestId !== null;
    }

    public function getIdentifier(): string
    {
        return $this->guestId ?? (string) $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'guest_id' => $this->guestId,
        ];
    }
}
