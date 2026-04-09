<?php

namespace App\Application\Match\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class StartMatchTimerAction
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function execute(string $matchId, Carbon $startedAt, int $durationSeconds): void
    {
        Log::info("Starting timer for match {$matchId}, duration: {$durationSeconds}s");

        $this->timerStorage->addTimer(TimerType::Match ->value, $matchId, $startedAt, $durationSeconds);

        $this->loop->addTimer($durationSeconds, function () use ($matchId) {
            Log::info("Timer expired for match {$matchId}, calling API to complete");

            if ($this->timerStorage->hasTimer(TimerType::Match ->value, $matchId)) {
                Log::info("Timer for match {$matchId} is valid, proceeding to expire match.");
                $this->apiClient->expireMatch($matchId);
                $this->timerStorage->removeTimer(TimerType::Match ->value, $matchId);
            } else {
                Log::info("Timer for match {$matchId} was already removed, skipping expiration.");
            }
        });
    }
}
