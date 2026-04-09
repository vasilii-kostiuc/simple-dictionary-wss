<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\MatchCompletedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use Illuminate\Support\Facades\Log;

class MatchCompletedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(mixed $payload): void
    {
        $data = $payload['data'] ?? [];

        $participants = $data['participants'] ?? [];

        if (empty($participants)) {
            Log::error('MatchCompletedHandler: No participants in payload', ['payload' => $payload]);

            return;
        }

        $message = new MatchCompletedMessage($data);

        foreach ($participants as $participant) {
            $userId = $participant['user_id'] ?? null;
            $guestId = $participant['guest_id'] ?? null;

            if ($userId) {
                $this->sender->sendToIdentifier((string) $userId, $message);
            }

            if ($guestId) {
                $this->sender->sendToIdentifier($guestId, $message);
            }
        }
    }
}
