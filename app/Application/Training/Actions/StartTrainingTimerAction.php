<?php

namespace App\Application\Training\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class StartTrainingTimerAction
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function execute(string $trainingId, Carbon $startedAt, int $durationSeconds): void
    {
        Log::info("Starting timer for training {$trainingId}, duration: {$durationSeconds}s");

        $this->timerStorage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);

        $this->loop->addTimer($durationSeconds, function () use ($trainingId) {
            Log::info("Timer expired for training {$trainingId}, calling API to complete");

            if ($this->timerStorage->hasTimer(TimerType::Training->value, $trainingId)) {
                Log::info("Timer for training {$trainingId} is valid, proceeding to expire training.");
                $this->apiClient->expire($trainingId);
                $this->timerStorage->removeTimer(TimerType::Training->value, $trainingId);
            } else {
                Log::info("Timer for training {$trainingId} was already removed, skipping expiration.");
            }
        });
    }
}
