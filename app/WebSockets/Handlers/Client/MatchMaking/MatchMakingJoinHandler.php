<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\Application\MatchMaking\Actions\JoinMatchMakingAction;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchMaking\MatchMakingJoinSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingJoinHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly JoinMatchMakingAction $joinAction,
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $user = $this->clientsStorage->getUserData($from);

        try {
            $result = $this->joinAction->execute($user, $payload['data'] ?? []);
            $this->sender->sendToConnection($from, new MatchMakingJoinSuccessMessage($result['matchType'], $result['matchParams']));
        } catch (MatchMakingException $e) {
            $this->sender->sendToConnection($from, new ErrorMessage($e->getErrorCode(), $payload ?? []));
        }
    }
}
