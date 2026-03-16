<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\ApiClients\GuzzleSimpleDictionaryApiClient;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingChallengeHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly GuzzleSimpleDictionaryApiClient $apiClient,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $userData = $this->clientsStorage->getUserData($from);

        $opponentId = $data['opponent_id'] ?? null;
        if ($opponentId === null) {
            $from->send(new ErrorMessage('opponent_id_required', $payload ?? []));
        }

        if (! $this->matchMakingQueue->isUserInQueue($opponentId)) {
            $from->send(new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $matchParams = $this->matchMakingQueue->extract($opponentId);

        if ($matchParams === null) {
            $from->send(new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $this->matchMakingQueue->remove($userData->id);
        $this->matchMakingQueue->remove($opponentId);


        $participants = [
            ['id' => $userData->id, 'type' => 'user'],
            ['id' => $opponentId, 'type' => 'user'],
        ];

        $this->apiClient->createMatch($participants, $matchParams);
    }
}
