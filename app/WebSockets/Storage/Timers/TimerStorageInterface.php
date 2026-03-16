<?php

namespace App\WebSockets\Storage\Timers;

use Carbon\Carbon;

interface TimerStorageInterface
{
    public function addTimer(string $type, string $id, Carbon $startedAt, int $durationSeconds): void;

    public function removeTimer(string $type, string $id): void;

    public function getExpiredTimers(): array;

    public function hasTimer(string $type, string $id): bool;

    public function getTimer(string $type, string $id): ?array;
}
