<?php

namespace App\Domain\Match\Enums;

enum MatchCompletionType: string
{
    case Time = 'time';
    case Steps = 'steps';
}
