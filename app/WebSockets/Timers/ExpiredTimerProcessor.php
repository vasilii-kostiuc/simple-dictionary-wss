<?php

namespace App\WebSockets\Timers;

use App\Application\Training\Actions\ProcessExpiredTimersAction;

class ExpiredTimerProcessor
{
    public function __construct(
        private readonly ProcessExpiredTimersAction $processExpiredTimersAction,
    ) {
    }

    public function process(): void
    {
        $this->processExpiredTimersAction->execute();
    }
}
