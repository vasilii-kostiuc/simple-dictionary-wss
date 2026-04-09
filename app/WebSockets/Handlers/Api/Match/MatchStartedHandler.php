<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\Application\Match\Actions\StartMatchTimerAction;
use App\Domain\Match\Enums\MatchCompletionType;
use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\MatchStartedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MatchStartedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly WebSocketMessageSenderInterface $sender,
        private readonly StartMatchTimerAction $startMatchTimerAction,
    ) {
    }

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
                $this->sender->sendToIdentifier((string) $userId, $message);
            }

            $guestId = $participant['guest_id'] ?? null;
            if ($guestId) {
                Log::info('Sending match started message to guest', ['guest_id' => $guestId]);
                $this->sender->sendToIdentifier($guestId, $message);
            }
        }

        $completionType = isset($data['completion_type']) ? MatchCompletionType::from($data['completion_type']) : null;

        if ($completionType === MatchCompletionType::Time) {
            $startedAt = Carbon::parse($data['started_at']);
            $this->startMatchTimerAction->execute($matchId, $startedAt, $data['completion_type_params']['duration'] * 60);
        }
    }
}
