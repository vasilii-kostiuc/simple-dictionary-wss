<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Events\MatchMaking\MatchMakingQueueUpdatedEvent;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchMaking\MatchMakingChallengeSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
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
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        Log::info(__METHOD__.' called');
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $userData = $this->clientsStorage->getUserData($from);
        info("userData :", $this->clientsStorage->getUserData($from)->toArray());

        $opponentId = isset($data['opponent_id']) ? (string) $data['opponent_id'] : null;
        Log::info('Received opponent_id: '.$opponentId);

        if ($opponentId === null) {
            $this->sender->sendToConnection($from, new ErrorMessage('opponent_id_required', $payload ?? []));

            return;
        }

        Log::info("User {$userData->getIdentifier()} is challenging opponent with ID: $opponentId");
        Log::info('Current matchmaking queue: '.json_encode($this->matchMakingQueue->allQueues()));

        if (! $this->matchMakingQueue->isUserInQueue($opponentId)) {
            $this->sender->sendToConnection($from, new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $matchData = $this->matchMakingQueue->extract($opponentId);

        if ($matchData === null) {
            $this->sender->sendToConnection($from, new ErrorMessage('opponent_not_in_queue', $payload ?? []));

            return;
        }

        $currentParticipant = $userData->isGuest()
            ? ['id' => $userData->getIdentifier(), 'type' => 'guest', 'name' => $userData->name, 'avatar' => $userData->avatar]
            : ['id' => $userData->getIdentifier(), 'type' => 'user'];

        $opponentParticipant = ($matchData['guestId'] ?? null)
            ? ['id' => $matchData['guestId'], 'type' => 'guest', 'name' => $matchData['name'], 'avatar' => $matchData['avatar']]
            : ['id' => $matchData['userId'], 'type' => 'user'];

        $participants = [$currentParticipant, $opponentParticipant];

        $createResult = $this->apiClient->createMatch($participants, $matchData['matchParams']);

        info(__METHOD__.' Match creation result: '.json_encode($createResult));

        $this->sender->sendToConnection($from, new MatchMakingChallengeSuccessMessage($createResult ?? []));

        $this->matchMakingQueue->remove($userData->getIdentifier());

        event(new MatchMakingQueueUpdatedEvent);
    }
}
