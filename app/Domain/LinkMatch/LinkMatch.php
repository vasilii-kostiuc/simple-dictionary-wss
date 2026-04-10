<?php

namespace App\Domain\LinkMatch;

use App\Domain\LinkMatch\LinkMatchStatus;
use App\Domain\Shared\Identity\UserIdentity;

final class LinkMatch
{
    public function __construct(
        public readonly string $id,
        public readonly string $token,
        public readonly int $participantsLimit,
        public readonly LinkMatchStatus $status,

        public array $payload = [],

        public readonly ?string $matchId = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->status === LinkMatchStatus::Pending;
    }

}