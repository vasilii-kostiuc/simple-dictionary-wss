<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\WebSockets\Events\MatchMaking\MatchMakingLeaveEvent;
use App\WebSockets\Messages\MatchMaking\MatchMakingLeaveSuccessMessage;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingLeaveHandler implements MessageHandlerInterface
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
    
        $this->matchMakingQueue->remove($userId);

        $from->send(
            new MatchMakingLeaveSuccessMessage()
        );
        
        event(new MatchMakingLeaveEvent($userId));
    }
}
