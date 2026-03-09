<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\WebSockets\Enums\MatchType;
use App\WebSockets\Events\MatchMaking\MatchMakingJoinedEvent;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\MatchMaking\MatchMakingJoinSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingJoinHandler implements MessageHandlerInterface
{
    private ClientsStorageInterface $clientsStorage;
    private MatchMakingQueueInterface $matchMakingQueue;
    private Dispatcher $dispatcher;

    public function __construct(
        ClientsStorageInterface $clientsStorage,
        MatchMakingQueueInterface $matchMakingQueue,
        Dispatcher $dispatcher
    ) {
        $this->clientsStorage = $clientsStorage;
        $this->matchMakingQueue = $matchMakingQueue;
        $this->dispatcher = $dispatcher;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $data = json_decode($msg->getPayload(), true);
        $userId = $this->clientsStorage->getUserIdByConnection($from);
        $matchType = MatchType::from($data['match_type'] ?? MatchType::Steps) ?? MatchType::Steps;
        $matchParams = ['match_type' => $matchType->value];
        $matchParams = array_merge($matchParams, $data['match_params'] ?? []);

        $this->matchMakingQueue->add($userId, $matchParams);

        $this->dispatcher->dispatch(new MatchMakingJoinedEvent($userId, $matchParams));

        $from->send(
            (new MatchMakingJoinSuccessMessage($matchType, $matchParams))->toJson()
        );
    }
}
