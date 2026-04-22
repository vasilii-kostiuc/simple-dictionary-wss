<?php

namespace App\WebSockets\Lifecycle;

use App\Application\LinkMatchRoom\Actions\DisconnectFromLinkMatchRoomAction;
use App\Application\MatchMaking\Actions\LeaveMatchMakingAction;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Infrastructure\Metrics\WsMetricsInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleService
{
    private string $nodeId;

    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
        private readonly DisconnectFromLinkMatchRoomAction $disconnectFromRoomAction,
        private readonly LeaveMatchMakingAction $leaveMatchMakingAction,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly WsMetricsInterface $metrics,
    ) {
        $this->nodeId = env('WSS_NODE_ID', gethostname());
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        Log::info('[{node}] Connection opened', ['node' => $this->nodeId, 'conn_id' => $conn->resourceId]);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $identity = $this->clientRegistry->getIdentity($conn);
        $channels = $this->subscriptionsStorage->getChannelsByConnection($conn);

        Log::info('[{node}] Connection closed', [
            'node' => $this->nodeId,
            'conn_id' => $conn->resourceId,
            'identifier' => $identity?->getIdentifier(),
            'channels' => $channels,
        ]);

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

        foreach ($channels as $channel) {
            $this->metrics->activeSubscriptionRemoved($channel);
        }

        $this->clientRegistry->forget($conn);
        $this->subscriptionsStorage->unsubscribeAll($conn);
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        Log::error('[{node}] Connection error', [
            'node' => $this->nodeId,
            'conn_id' => $conn->resourceId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
