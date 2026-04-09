<?php

namespace App\WebSockets\Enums;

enum SubscriptionChannelPattern: string
{
    case Training = 'training.*';
    case Match = 'match.*';
    case MatchmakingQueue = 'matchmaking.queue';
}
