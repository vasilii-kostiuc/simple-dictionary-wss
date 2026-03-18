<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Messages\Match\MatchCreatedMessage;
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
                $connection = $this->clientsStorage->getConnectionByUserId($userId);
                if ($connection) {
                    Log::info('Sending match created message to connection', ['connection_id' => $connection->resourceId]);
                    $connection->send(new MatchCreatedMessage($data));
                }
            }

            //add for guest users as well
            $guestId = $participant['guest_id'] ?? null;


        }

    }

}
