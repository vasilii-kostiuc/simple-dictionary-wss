<?php

namespace App\WebSockets\Enums;

enum MatchCompletionType: string
{
    case Time = 'time';
    case Steps = 'steps';
}
