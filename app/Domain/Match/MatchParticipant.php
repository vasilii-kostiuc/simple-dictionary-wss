<?php

namespace App\Domain\Match;

use App\Domain\Shared\Identity\ClientIdentity;

final class MatchParticipant
{
    private function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $name = null,
        public readonly ?string $avatar = null,
    ) {
    }

    public static function fromIdentity(ClientIdentity $identity): self
    {
        return new self(
            id: $identity->getIdentifier(),
            type: $identity->isGuest() ? 'guest' : 'user',
            name: $identity->isGuest() ? $identity->name : null,
            avatar: $identity->isGuest() ? $identity->avatar : null,
        );
    }

    public function toArray(): array
    {
        $arr = ['id' => $this->id, 'type' => $this->type];

        if ($this->name !== null) {
            $arr['name'] = $this->name;
        }

        if ($this->avatar !== null) {
            $arr['avatar'] = $this->avatar;
        }

        return $arr;
    }
}
