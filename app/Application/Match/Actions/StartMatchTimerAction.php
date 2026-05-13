<?php

namespace App\Application\Match\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\Contracts\TimerSchedulerInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
use Carbon\Carbon;

class StartMatchTimerAction
{
    public function __construct(
        private readonly TimerSchedulerInterface $timerScheduler,
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {}

    public function execute(string $matchId, Carbon $startedAt, int $durationSeconds): void
    {
        $this->timerStorage->addTimer(TimerType::Match->value, $matchId, $startedAt, $durationSeconds);

        $this->timerScheduler->scheduleOnce($durationSeconds, function () use ($matchId) {
            if ($this->timerStorage->hasTimer(TimerType::Match->value, $matchId)) {
                $this->apiClient->expireMatch($matchId);
                $this->timerStorage->removeTimer(TimerType::Match->value, $matchId);
            }
        });
    }
}
