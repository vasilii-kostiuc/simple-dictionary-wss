<?php

namespace App\WebSockets\Timers;

use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class PeriodicTimerScheduler
{
    private bool $started = false;

    public function __construct(
        private readonly LoopInterface $loop,
        private readonly ExpiredTimerProcessor $expiredTimerProcessor,
    ) {}

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->loop->addPeriodicTimer(5, function (): void {
            $this->expiredTimerProcessor->process();
        });

        $this->started = true;

        Log::info('Expired timers checker started (interval: 5s)');
    }
}
