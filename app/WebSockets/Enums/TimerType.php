<?php

namespace App\WebSockets\Enums;

enum TimerType: string
{
    case Training = 'training';
    case Match = 'match';
}
