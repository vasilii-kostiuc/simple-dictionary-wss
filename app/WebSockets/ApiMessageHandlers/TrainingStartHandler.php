<?php

namespace App\WebSockets\ApiMessageHandlers;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Enums\TrainingCompletionType;
use App\WebSockets\Storage\Timers\TrainingTimerStorageInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class TrainingStartHandler implements ApiMessageHandlerInterface
{
    private LoopInterface $loop;
    private TrainingTimerStorageInterface $timerStorage;
    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    public function __construct(LoopInterface $loop, TrainingTimerStorageInterface $timerStorage, SimpleDictionaryApiClientInterface $simpleDictionaryApiClient)
    {
        $this->loop = $loop;
        $this->timerStorage = $timerStorage;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
    }

    public function handle(string $channel, mixed $data): void
    {
        Log::info('Training started', $data);
        $trainingId = $data['training_id'] ?? null;
        $completionType = $data['completion_type'] ? TrainingCompletionType::from($data['completion_type']) : null;

        if (!$trainingId) {
            Log::error('TrainingStartHandler: Missing training_id', ['data' => $data]);
            return;
        }

        if ($completionType == TrainingCompletionType::Time) {
            $startedAt = Carbon::parse($data['started_at']);
            $this->startTimer($trainingId, $startedAt, $data['completion_type_params']['duration'] * 60);
        }
    }

    private function startTimer(string $trainingId, Carbon $startedAt, int $durationSeconds): void
    {
        Log::info("Starting timer for training {$trainingId}, duration: {$durationSeconds}s");

        $this->timerStorage->addTimer($trainingId, $startedAt, $durationSeconds);
        $this->loop->addTimer($durationSeconds, function () use ($trainingId) {
            Log::info("Timer expired for training {$trainingId}, calling API to complete");

            if ($this->timerStorage->hasTimer($trainingId)) {
                Log::info("Timer for training {$trainingId} is valid, proceeding to expire training.");

                $this->simpleDictionaryApiClient->expire($trainingId);
                $this->timerStorage->removeTimer($trainingId);
            } else {
                Log::info("Timer for training {$trainingId} was already removed, skipping expiration.");
                return;
            }
        });
    }
}
