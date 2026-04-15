<?php

namespace App\WebSockets\Lifecycle;

use App\Application\LinkMatchRoom\Actions\DisconnectFromLinkMatchRoomAction;
use App\Application\MatchMaking\Actions\LeaveMatchMakingAction;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleService
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
        private readonly DisconnectFromLinkMatchRoomAction $disconnectFromRoomAction,
        private readonly LeaveMatchMakingAction $leaveMatchMakingAction,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {}

    public function onOpen(ConnectionInterface $conn): void {}

    public function onClose(ConnectionInterface $conn): void
    {
        $identity = $this->clientRegistry->getIdentity($conn);
        $channels = $this->subscriptionsStorage->getChannelsByConnection($conn);

        if ($identity !== null) {
            foreach ($channels as $channel) {
                if (str_starts_with($channel, 'link_match_room.')) {
                    $roomId = substr($channel, strlen('link_match_room.'));
                    $this->disconnectFromRoomAction->execute($identity, $roomId);
                }
            }

            if ($this->matchMakingQueue->isUserInQueue($identity->getIdentifier())) {
                $this->leaveMatchMakingAction->execute($identity->getIdentifier());
            }
        }

        $this->clientRegistry->forget($conn);
        $this->subscriptionsStorage->unsubscribeAll($conn);
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        Log::error(__METHOD__.' '.$e->getMessage().PHP_EOL.$e->getTraceAsString());
    }
}
