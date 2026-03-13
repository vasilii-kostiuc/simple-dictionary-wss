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
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {}

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $userData = $this->clientsStorage->getUserData($from);

        if ($userData === null) {
            $from->send(new ErrorMessage('not_authorized', $payload ?? []));

            return;
        }

        $matchType = MatchType::tryFrom($data['match_type'] ?? MatchType::Steps->value);
        if ($matchType === null) {
            $from->send(new ErrorMessage('invalid_match_type', $payload ?? []));

            return;
        }

        $matchParams = ['match_type' => $matchType->value];
        $matchParams = array_merge($matchParams, $data['match_params'] ?? []);

        $this->matchMakingQueue->add($userData, $matchParams);

        $from->send(new MatchMakingJoinSuccessMessage($matchType, $matchParams));

        event(new MatchMakingJoinedEvent($userData->id, $matchParams));
    }
}
