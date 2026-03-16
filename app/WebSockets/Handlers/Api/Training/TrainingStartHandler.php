<?php

namespace App\WebSockets\Handlers\Api\Training;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Enums\TimerType;
use App\WebSockets\Enums\TrainingCompletionType;
use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class TrainingStartHandler implements ApiMessageHandlerInterface
{
    private LoopInterface $loop;

    private TimerStorageInterface $timerStorage;

    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    public function __construct(LoopInterface $loop, TimerStorageInterface $timerStorage, SimpleDictionaryApiClientInterface $simpleDictionaryApiClient)
    {
        $this->loop = $loop;
        $this->timerStorage = $timerStorage;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
    }

    public function handle(string $channel, mixed $payload): void
    {
        Log::info('Training started', $payload);
        $data = $payload['data'] ?? [];
        $trainingId = $data['training_id'] ?? null;
        $completionType = isset($data['completion_type']) ? TrainingCompletionType::from($data['completion_type']) : null;

        if (! $trainingId) {
            Log::error('TrainingStartHandler: Missing training_id', ['payload' => $payload]);

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

        $this->timerStorage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);
        $this->loop->addTimer($durationSeconds, function () use ($trainingId) {
            Log::info("Timer expired for training {$trainingId}, calling API to complete");

            if ($this->timerStorage->hasTimer(TimerType::Training->value, $trainingId)) {
                Log::info("Timer for training {$trainingId} is valid, proceeding to expire training.");

                $this->simpleDictionaryApiClient->expire($trainingId);
                $this->timerStorage->removeTimer(TimerType::Training->value, $trainingId);
            } else {
                Log::info("Timer for training {$trainingId} was already removed, skipping expiration.");

                return;
            }
        });
    }
}

