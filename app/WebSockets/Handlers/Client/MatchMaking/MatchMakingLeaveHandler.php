<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\Application\MatchMaking\Actions\LeaveMatchMakingAction;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\MatchMaking\MatchMakingLeaveSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingLeaveHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly LeaveMatchMakingAction $leaveAction,
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $identifier = $this->clientsStorage->getIdentifierByConnection($from);

        $this->leaveAction->execute($identifier);

        $this->sender->sendToConnection($from, new MatchMakingLeaveSuccessMessage);
    }
}
