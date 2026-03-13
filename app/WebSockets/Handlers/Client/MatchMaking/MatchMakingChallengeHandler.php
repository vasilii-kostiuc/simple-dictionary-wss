<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\WebSockets\Enums\MatchType;
use App\WebSockets\Events\MatchMaking\MatchMakingJoinedEvent;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchMaking\MatchMakingJoinSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingChallengeHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $userData = $this->clientsStorage->getUserData($from);

        if ($userData === null) {
            $from->send(new ErrorMessage('not_authorized', $payload ?? []));

            return;
        }

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




    }
}
