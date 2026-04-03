<?php

namespace App\WebSockets\Enums;

enum ErrorCode: string
{
    case InvalidJson = 'invalid_json';
    case NotAuthorized = 'not_authorized';

    case TokenRequired = 'token_required';
    case InvalidToken = 'invalid_token';

    case InvalidGuestId = 'invalid_guest_id';

    case OpponentIdRequired = 'opponent_id_required';
    case OpponentNotInQueue = 'opponent_not_in_queue';

    case InvalidMatchType = 'invalid_match_type';

    case ChannelIsRequired = 'channel_is_required';
    case ChannelIsNotAllowed = 'channel_is_not_allowed';

    case UnknownMessage = 'unknown_message';
}

