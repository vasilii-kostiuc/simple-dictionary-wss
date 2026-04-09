<?php

namespace App\Domain\Shared\Enums;

enum TimerType: string
{
    case Training = 'training';
    case Match = 'match';
}
