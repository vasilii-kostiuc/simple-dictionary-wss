<?php

namespace App\WebSockets\Enums;

enum ClientRequestType: string
{
    case Auth = 'auth';
    case GuestAuth = 'guest_auth';

    case Subscribe = 'subscribe';
    case Unsubscribe = 'unsubscribe';

    case MatchmakingJoin = 'matchmaking.join';
    case MatchmakingLeave = 'matchmaking.leave';
    case MatchmakingChallenge = 'matchmaking.challenge';
}

