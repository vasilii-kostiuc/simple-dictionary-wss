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

class MatchMakingJoinHandler implements MessageHandlerInterface
{
    private ClientsStorageInterface $clientsStorage;
    private MatchMakingQueueInterface $matchMakingQueue;

    public function __construct(
        ClientsStorageInterface $clientsStorage,
        MatchMakingQueueInterface $matchMakingQueue,
    ) {
        $this->clientsStorage = $clientsStorage;
        $this->matchMakingQueue = $matchMakingQueue;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $data = json_decode($msg->getPayload(), true);
        $userId = $this->clientsStorage->getUserIdByConnection($from);

        if($userId === null) {
            $from->send(new ErrorMessage('not_authorized', $data??[]));
            return;
        }

        $matchType = MatchType::tryFrom($data['match_type'] ?? MatchType::Steps->value);
        
        if ($matchType === null) {
            $from->send(new ErrorMessage('invalid_match_type', $data ?? []));
            return;
        }

        $matchParams = ['match_type' => $matchType->value];
        $matchParams = array_merge($matchParams, $data['match_params'] ?? []);

        $this->matchMakingQueue->add($userId, $matchParams);

        $from->send(
            new MatchMakingJoinSuccessMessage($matchType, $matchParams)
        );

        event(new MatchMakingJoinedEvent($userId, $matchParams));
    }
}
