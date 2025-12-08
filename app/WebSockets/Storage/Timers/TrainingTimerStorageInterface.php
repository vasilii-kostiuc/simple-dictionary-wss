<?php

namespace App\WebSockets\Storage\Timers;

use Carbon\Carbon;

interface TrainingTimerStorageInterface
{
    public function addTimer(string $trainingId, Carbon $startedAt, int $durationSeconds): void;

    public function removeTimer(string $trainingId): void;

    public function getExpiredTimers(): array;

    public function hasTimer(string $trainingId): bool;

    public function getTimer(string $trainingId): ?array;
}
