<?php

namespace App\Domain\LinkMatchRoom;

enum LinkMatchRoomStatus: string
{
    case WaitingForPlayers = 'waiting_for_players';
    case Full = 'full';
    case MatchCreating = 'match_creating';
    case MatchCreated = 'match_created';

    case MatchCreationFailed = 'match_creation_failed';
    case Completed = 'completed';

    case Closed = 'closed';
}