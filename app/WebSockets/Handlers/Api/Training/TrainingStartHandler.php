<?php

namespace App\WebSockets\Handlers\Api\Training;

use App\Application\Training\Actions\StartTrainingTimerAction;
use App\Domain\Training\Enums\TrainingCompletionType;
use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainingStartHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly StartTrainingTimerAction $startTimerAction,
    ) {}

    public function handle(mixed $payload): void
    {
        $data = $payload['data'] ?? [];
        $trainingId = $data['training_id'] ?? null;
        $completionType = isset($data['completion_type']) ? TrainingCompletionType::from($data['completion_type']) : null;

        if (! $trainingId) {
            Log::error('TrainingStartHandler: Missing training_id', ['payload' => $payload]);

            return;
        }

        if ($completionType === TrainingCompletionType::Time) {
            $startedAt = Carbon::parse($data['started_at']);
            $durationSeconds = $data['completion_type_params']['duration'] * 60;

            $this->startTimerAction->execute($trainingId, $startedAt, $durationSeconds);
        }
    }
}
