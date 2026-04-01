<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\NextStepGeneratedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use Illuminate\Support\Facades\Log;

class MatchStepGeneratedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(mixed $payload): void
    {
        info(__METHOD__.' Next step generated ', $payload);
        $data = $payload['data'] ?? [];

        $userId = $data['user_id'] ?? null;
        $guestId = $data['guest_id'] ?? null;

        if (! $userId && ! $guestId) {
            Log::error('MatchStepGeneratedHandler: Missing user_id and guest_id', ['payload' => $payload]);

            return;
        }

        $message = new NextStepGeneratedMessage($data);

        if ($userId) {
            Log::info('Sending next_step_generated to user', ['user_id' => $userId]);
            $this->sender->sendToIdentifier((string) $userId, $message);
        }

        if ($guestId) {
            Log::info('Sending next_step_generated to guest', ['guest_id' => $guestId]);
            $this->sender->sendToIdentifier($guestId, $message);
        }
    }
}
