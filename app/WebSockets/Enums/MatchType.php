<?php

namespace App\WebSockets\Enums;

enum MatchType: string
{
    case Time = 'time';
    case Steps = 'steps';
}
