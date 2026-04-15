<?php

namespace App\Domain\LinkMatchRoom\Events;

use App\Domain\Match\MatchParams;
use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Identity\ClientIdentity;

final class RoomBecameFullEvent implements DomainEvent
{
    /**
     * @param  ClientIdentity[]  $participants
     */
    public function __construct(
        public readonly string $roomId,
        public readonly array $participants,
        public readonly MatchParams $matchParams,
    ) {
    }
}
