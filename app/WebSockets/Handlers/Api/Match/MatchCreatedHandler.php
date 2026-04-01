<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\MatchCreatedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use Illuminate\Support\Facades\Log;

class MatchCreatedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly WebSocketMessageSenderInterface $sender
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
            $guestId = $participant['guest_id'] ?? null;

            if ($userId) {
                Log::info('Sending match created message to user', ['user_id' => $userId]);
                $this->sender->sendToIdentifier((string) $userId, new MatchCreatedMessage($data));
            }

            if ($guestId) {
                Log::info('Sending match created message to guest', ['guest_id' => $guestId]);
                $this->sender->sendToIdentifier($guestId, new MatchCreatedMessage($data));
            }
        }

    }
}
