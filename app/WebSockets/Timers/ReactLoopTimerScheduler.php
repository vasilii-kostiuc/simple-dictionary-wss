<?php

namespace App\WebSockets\Timers;

use App\Application\Contracts\TimerSchedulerInterface;
use React\EventLoop\LoopInterface;

class ReactLoopTimerScheduler implements TimerSchedulerInterface
{
    public function __construct(private readonly LoopInterface $loop) {}

    public function scheduleOnce(int $delaySeconds, callable $callback): void
    {
        $this->loop->addTimer($delaySeconds, $callback);
    }
}
