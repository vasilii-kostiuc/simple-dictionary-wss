<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Messages\Match\MatchStartedMessage;
use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Enums\TimerType;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class MatchStartedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly ClientsStorageInterface $clientsStorage
    ) {
    }

    public function handle(mixed $payload): void
    {
        info(__METHOD__.' Match created ', $payload);
        $data = $payload['data'] ?? [];
        $matchId = $data['id'] ?? null;

        if (! $matchId) {
            Log::error('MatchCreatedHandler: Missing id', ['payload' => $payload]);

            return;
        }

        $participants = $data['participants'] ?? [];

        foreach ($participants as $participant) {
            $userId = $participant['user_id'] ?? null;

            if ($userId) {
                $connection = $this->clientsStorage->getConnectionByUserId($userId);
                if ($connection) {
                    Log::info('Sending match started message to connection', ['connection_id' => $connection->resourceId]);
                    $connection->send(new MatchStartedMessage($data));
                }
            }

            //add for guest users as well
            $guestId = $participant['guest_id'] ?? null;


        }
    }

    private function startTimer(string $trainingId, Carbon $startedAt, int $durationSeconds): void
    {
        Log::info("Starting timer for training {$trainingId}, duration: {$durationSeconds}s");

        $this->timerStorage->addTimer(TimerType::Match ->value, $trainingId, $startedAt, $durationSeconds);
        $this->loop->addTimer($durationSeconds, function () use ($trainingId) {
            Log::info("Timer expired for training {$trainingId}, calling API to complete");

            if ($this->timerStorage->hasTimer(TimerType::Match ->value, $trainingId)) {
                Log::info("Timer for training {$trainingId} is valid, proceeding to expire training.");

                $this->simpleDictionaryApiClient->expire($trainingId);
                $this->timerStorage->removeTimer(TimerType::Match ->value, $trainingId);
            } else {
                Log::info("Timer for training {$trainingId} was already removed, skipping expiration.");

                return;
            }
        });
    }
}
