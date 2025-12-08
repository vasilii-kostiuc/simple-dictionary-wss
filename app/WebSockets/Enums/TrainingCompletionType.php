<?php

namespace App\WebSockets\Enums;

enum TrainingCompletionType: string
{
    case Time = 'time';
    case Steps = 'steps';
    case Unlimited = 'unlimited';
}
