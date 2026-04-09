<?php

namespace App\Domain\MatchMaking\Enums;

enum MatchType: string
{
    case Time = 'time';
    case Steps = 'steps';
}
