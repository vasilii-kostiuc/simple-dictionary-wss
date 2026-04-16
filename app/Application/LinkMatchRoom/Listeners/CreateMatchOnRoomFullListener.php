<?php

namespace App\Application\LinkMatchRoom\Listeners;

use App\Application\Match\Actions\CreateMatchAction;
use App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent;
use App\Domain\Match\MatchParticipant;
use App\Domain\Shared\Identity\ClientIdentity;

class CreateMatchOnRoomFullListener
{
    public function __construct(
        private readonly CreateMatchAction $createMatchAction,
    ) {}

    public function handle(RoomBecameFullEvent $event): void
    {
        $participants = array_map(
            fn (ClientIdentity $identity) => MatchParticipant::fromIdentity($identity),
            $event->participants,
        );

        $this->createMatchAction->execute($participants, $event->matchParams);
    }
}
