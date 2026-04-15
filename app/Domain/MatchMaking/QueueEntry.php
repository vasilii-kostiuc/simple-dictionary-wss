<?php

namespace App\Domain\MatchMaking;

use App\Domain\Match\MatchParams;
use App\Domain\Shared\Identity\ClientIdentity;

final class QueueEntry
{
    public function __construct(
        public readonly ClientIdentity $identity,
        public readonly MatchParams $matchParams,
    ) {}

    public function toArray(): array
    {
        return [
            'userId' => $this->identity->id,
            'guestId' => $this->identity->guestId,
            'identifier' => $this->identity->getIdentifier(),
            'name' => $this->identity->name,
            'email' => $this->identity->email,
            'avatar' => $this->identity->avatar,
            'matchParams' => $this->matchParams->toArray(),
        ];
    }
}
