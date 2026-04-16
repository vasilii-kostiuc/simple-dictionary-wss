<?php

namespace App\Application\Training\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
use Carbon\Carbon;
use React\EventLoop\LoopInterface;

class StartTrainingTimerAction
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {}

    public function execute(string $trainingId, Carbon $startedAt, int $durationSeconds): void
    {
        $this->timerStorage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);

        $this->loop->addTimer($durationSeconds, function () use ($trainingId) {
            if ($this->timerStorage->claimTimer(TimerType::Training->value, $trainingId)) {
                $this->apiClient->expire($trainingId);
                $this->timerStorage->removeTimer(TimerType::Training->value, $trainingId);
            }
        });
    }
}
