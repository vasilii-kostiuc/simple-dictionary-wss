<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Events\MatchMaking\MatchMakingQueueUpdatedEvent;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingChallengeHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        Log::info(__METHOD__.' called');
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $userData = $this->clientsStorage->getUserData($from);

        $opponentId = isset($data['opponent_id']) ? (int) $data['opponent_id'] : null;
        Log::info('Received opponent_id: '.$opponentId);

        if ($opponentId === null) {
            $from->send(new ErrorMessage('opponent_id_required', $payload ?? []));

            return;
        }

        Log::info("User {$userData->id} is challenging opponent with ID: $opponentId");
        Log::info('Current matchmaking queue: '.json_encode($this->matchMakingQueue->allQueues()));

        if (! $this->matchMakingQueue->isUserInQueue($opponentId)) {
            $from->send(new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $matchData = $this->matchMakingQueue->extract($opponentId);

        if ($matchData === null) {
            $from->send(new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $participants = [
            ['id' => $userData->id, 'type' => 'user'],
            ['id' => $opponentId, 'type' => 'user'],
        ];

        $createResult = $this->apiClient->createMatch($participants, $matchData['matchParams']);

        info(__METHOD__.' Match creation result: '.json_encode($createResult));

        $from->send(json_encode(['type' => 'matchmaking_challenge_success', 'data' => $createResult]));

        $this->matchMakingQueue->remove($userData->id);

        event(new MatchMakingQueueUpdatedEvent());
    }
}
