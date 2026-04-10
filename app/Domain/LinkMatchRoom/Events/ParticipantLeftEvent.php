<?php

namespace App\Domain\LinkMatchRoom\Events;

use App\Domain\Shared\DomainEvent;

final class ParticipantLeftEvent implements DomainEvent
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $participantId,
        public readonly array $participants,
    ) {
    }
}
