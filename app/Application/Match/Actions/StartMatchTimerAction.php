<?php

namespace App\Application\Match\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
use Carbon\Carbon;
use React\EventLoop\LoopInterface;

class StartMatchTimerAction
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {}

    public function execute(string $matchId, Carbon $startedAt, int $durationSeconds): void
    {
        $this->timerStorage->addTimer(TimerType::Match->value, $matchId, $startedAt, $durationSeconds);

        $this->loop->addTimer($durationSeconds, function () use ($matchId) {
            if ($this->timerStorage->hasTimer(TimerType::Match->value, $matchId)) {
                $this->apiClient->expireMatch($matchId);
                $this->timerStorage->removeTimer(TimerType::Match->value, $matchId);
            }
        });
    }
}
