<?php

namespace App\Application\Contracts;

interface TimerSchedulerInterface
{
    public function scheduleOnce(int $delaySeconds, callable $callback): void;
}
