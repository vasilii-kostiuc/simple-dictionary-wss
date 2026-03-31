<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Enums\MatchCompletionType;
use App\WebSockets\Enums\TimerType;
use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\MatchStartedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class MatchStartedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly LoopInterface $loop,
        private readonly TimerStorageInterface $timerStorage,
        private readonly WebSocketMessageSenderInterface $sender,
        private readonly SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
    ) {}

    public function handle(mixed $payload): void
    {
        info(__METHOD__.' Match started ', $payload);
        $data = $payload['data'] ?? [];
        $matchId = $data['id'] ?? null;

        if (! $matchId) {
            Log::error('MatchStartedHandler: Missing id', ['payload' => $payload]);

            return;
        }

        $participants = $data['participants'] ?? [];
        $message = new MatchStartedMessage($data);

        foreach ($participants as $participant) {
            $userId = $participant['user_id'] ?? null;
            if ($userId) {
                Log::info('Sending match started message to user', ['user_id' => $userId]);
                $this->sender->sendToUser($userId, $message);
            }

            $guestId = $participant['guest_id'] ?? null;
            if ($guestId) {
                Log::info('Sending match started message to guest', ['guest_id' => $guestId]);
                $this->sender->sendToUser($guestId, $message);
            }
        }

        $completionType = isset($data['completion_type']) ? MatchCompletionType::from($data['completion_type']) : null;

        if ($completionType === MatchCompletionType::Time) {
            $startedAt = Carbon::parse($data['started_at']);
            $this->startTimer($matchId, $startedAt, $data['completion_type_params']['duration'] * 60);
        }
    }

    private function startTimer(string $matchId, Carbon $startedAt, int $durationSeconds): void
    {
        Log::info("Starting timer for match {$matchId}, duration: {$durationSeconds}s");

        $this->timerStorage->addTimer(TimerType::Match->value, $matchId, $startedAt, $durationSeconds);
        $this->loop->addTimer($durationSeconds, function () use ($matchId) {
            Log::info("Timer expired for match {$matchId}, calling API to complete");

            if ($this->timerStorage->hasTimer(TimerType::Match->value, $matchId)) {
                Log::info("Timer for match {$matchId} is valid, proceeding to expire match.");

                $this->simpleDictionaryApiClient->expireMatch($matchId);
                $this->timerStorage->removeTimer(TimerType::Match->value, $matchId);
            } else {
                Log::info("Timer for match {$matchId} was already removed, skipping expiration.");
            }
        });
    }
}
