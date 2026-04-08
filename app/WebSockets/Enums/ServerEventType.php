<?php

namespace App\WebSockets\Enums;

enum ServerEventType: string
{
    // Generic
    case Error = 'error';

    // Auth
    case AuthSuccess = 'auth_success';
    case GuestAuthSuccess = 'guest_auth_success';

    // Subscriptions
    case SubscribeSuccess = 'subscribe_success';
    case UnsubscribeSuccess = 'unsubscribe_success';

    // Matchmaking
    case MatchmakingJoinSuccess = 'matchmaking_join_success';
    case MatchmakingLeaveSuccess = 'matchmaking_leave_success';
    case MatchmakingChallengeSuccess = 'matchmaking_challenge_success';
    case MatchmakingQueueUpdated = 'matchmaking.queue.updated';

    // Training / Match
    case TrainingStarted = 'training_started';
    case TrainingCompleted = 'training_completed';
    case MatchCreated = 'match_created';
    case MatchStarted = 'match_started';
    case NextStepGenerated = 'next_step_generated';
    case MatchSummary = 'match_summary';
    case MatchCompleted = 'match_completed';
}

