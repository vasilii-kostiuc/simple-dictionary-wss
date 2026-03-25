<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\MatchSummaryMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Illuminate\Support\Facades\Log;

class MatchSummaryHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
    ) {
    }

    public function handle(mixed $payload): void
    {
        info(__METHOD__.' Match summary received ', $payload);
        $data = $payload['data'] ?? [];

        $participants = $data['participants'] ?? [];

        if (empty($participants)) {
            Log::error('MatchSummaryHandler: No participants in payload', ['payload' => $payload]);

            return;
        }

        $message = new MatchSummaryMessage($data);

        foreach ($participants as $participant) {
            $userId = $participant['user_id'] ?? null;
            $guestId = $participant['guest_id'] ?? null;

            if ($userId) {
                $connection = $this->clientsStorage->getConnectionByUserId($userId);
                if ($connection) {
                    Log::info('Sending match_summary to user', ['user_id' => $userId]);
                    $connection->send($message);
                }
            }

            if ($guestId) {
                $connection = $this->clientsStorage->getConnectionByUserId($guestId);
                if ($connection) {
                    Log::info('Sending match_summary to guest', ['guest_id' => $guestId]);
                    $connection->send($message);
                }
            }
        }
    }
}
