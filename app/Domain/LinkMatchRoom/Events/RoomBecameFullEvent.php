<?php

namespace App\Domain\LinkMatchRoom\Events;

use App\Domain\Shared\DomainEvent;

final class RoomBecameFullEvent implements DomainEvent
{
    public function __construct(
        public readonly string $roomId,
        public readonly array $participants,
    ) {}
}
