<?php

namespace App\WebSockets\Handlers\Api\Match;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Match\NextStepGeneratedMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Illuminate\Support\Facades\Log;

class MatchStepGeneratedHandler implements ApiMessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
    ) {}

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

        if ($userId) {
            $connection = $this->clientsStorage->getConnectionByUserId($userId);
            if ($connection) {
                Log::info('Sending next_step_generated to user', ['user_id' => $userId]);
                $connection->send(new NextStepGeneratedMessage($data));
            }
        }

        if ($guestId) {
            $connection = $this->clientsStorage->getConnectionByUserId($guestId);
            if ($connection) {
                Log::info('Sending next_step_generated to guest', ['guest_id' => $guestId]);
                $connection->send(new NextStepGeneratedMessage($data));
            }
        }
    }
}
