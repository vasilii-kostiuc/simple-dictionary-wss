<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Illuminate\Support\Facades\Log;

class MatchCreatedHandler implements ApiMessageHandlerInterface
{

    public function __construct(
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
                $connections = $this->clientsStorage->getConnectionsByUserId($userId);
                foreach ($connections as $connectionId => $conn) {
                    Log::info('Sending match created message to connection', ['connection_id' => $connectionId]);
                    $conn->send(json_encode([
                        'type' => 'match.created',
                        'data' => [
                            'id' => $matchId,
                            'participants' => $participants,
                        ],
                    ]));
                }
            }

            //add for guest users as well
            $guestId = $participant['guest_id'] ?? null;


        }

    }

}
