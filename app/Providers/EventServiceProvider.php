<?php

namespace App\Providers;

use App\Application\LinkMatchRoom\Listeners\CreateMatchOnRoomFullListener;
use App\Application\MatchMaking\Events\MatchMakingJoinedEvent;
use App\Application\MatchMaking\Events\MatchMakingLeaveEvent;
use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use App\Domain\LinkMatchRoom\Events\ParticipantJoinedEvent;
use App\Domain\LinkMatchRoom\Events\ParticipantLeftEvent;
use App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent;
use App\WebSockets\Listeners\LinkMatchRoom\PublishLinkMatchRoomJoinedListener;
use App\WebSockets\Listeners\LinkMatchRoom\PublishLinkMatchRoomLeftListener;
use App\WebSockets\Listeners\MatchMaking\PublishMatchMakingJoinedListener;
use App\WebSockets\Listeners\MatchMaking\PublishMatchMakingLeaveListener;
use App\WebSockets\Listeners\MatchMaking\PublishMatchMakingQueueUpdatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MatchMakingJoinedEvent::class => [
            PublishMatchMakingJoinedListener::class,
        ],
        MatchMakingLeaveEvent::class => [
            PublishMatchMakingLeaveListener::class,
        ],
        MatchMakingQueueUpdatedEvent::class => [
            PublishMatchMakingQueueUpdatedListener::class,
        ],
        RoomBecameFullEvent::class => [
            CreateMatchOnRoomFullListener::class,
        ],
        ParticipantJoinedEvent::class => [
            PublishLinkMatchRoomJoinedListener::class,
        ],
        ParticipantLeftEvent::class => [
            PublishLinkMatchRoomLeftListener::class,
        ],
    ];
}
