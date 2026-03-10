<?php

namespace App\WebSockets\Handlers\Client;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingJoinHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingLeaveHandler;
use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Handlers\Client\Subscription\UnsubscribeMessageHandler;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

class MessageHandlerFactory
{
    private SimpleDictionaryApiClientInterface $apiClient;
    private ClientsStorageInterface $clientsStorage;
    private SubscriptionsStorageInterface $subscriptionsStorage;
    private MatchMakingQueueInterface $matchMakingQueue;

    public function __construct(
        SimpleDictionaryApiClientInterface $apiClient,
        ClientsStorageInterface $clientsStorage,
        SubscriptionsStorageInterface $subscriptionsStorage,
        MatchMakingQueueInterface $matchMakingQueue
    ) {
        $this->apiClient = $apiClient;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->matchMakingQueue = $matchMakingQueue;
    }

    public function create(string $type): MessageHandlerInterface
    {
        return match ($type) {
            'auth' => new AuthMessageHandler($this->apiClient, $this->clientsStorage),
            'subscribe' => new SubscribeMessageHandler($this->subscriptionsStorage, $this->clientsStorage),
            'unsubscribe' => new UnsubscribeMessageHandler($this->subscriptionsStorage, $this->clientsStorage),
            'matchmaking.join' => new MatchMakingJoinHandler($this->clientsStorage, $this->matchMakingQueue),
            'matchmaking.leave' => new MatchMakingLeaveHandler($this->clientsStorage, $this->matchMakingQueue),
            default => new UnknownMessageHandler()
        };
    }
}
