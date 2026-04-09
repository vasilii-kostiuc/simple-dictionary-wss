<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\Application\MatchMaking\Actions\ChallengeMatchMakingAction;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchMaking\MatchMakingChallengeSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingChallengeHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly ChallengeMatchMakingAction $challengeAction,
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $user = $this->clientsStorage->getUserData($from);

        $opponentId = isset($data['opponent_id']) ? (string) $data['opponent_id'] : null;

        if ($opponentId === null) {
            $this->sender->sendToConnection($from, new ErrorMessage('opponent_id_required', $payload ?? []));

            return;
        }

        try {
            $createResult = $this->challengeAction->execute($user, $opponentId);
            $this->sender->sendToConnection($from, new MatchMakingChallengeSuccessMessage($createResult));
        } catch (MatchMakingException $e) {
            $this->sender->sendToConnection($from, new ErrorMessage($e->getErrorCode(), $payload ?? []));
        }
    }
}
