<?php

namespace App\WebSockets\Handlers\Client;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Enums\ClientRequestType;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingChallengeHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingJoinHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingLeaveHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingSubscribeHandler;
use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Handlers\Client\Subscription\UnsubscribeMessageHandler;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

class MessageHandlerFactory
{
    private SimpleDictionaryApiClientInterface $apiClient;

    private ClientsStorageInterface $clientsStorage;

    private SubscriptionsStorageInterface $subscriptionsStorage;

    private MatchMakingQueueInterface $matchMakingQueue;

    private WebSocketMessageSenderInterface $sender;

    public function __construct(
        SimpleDictionaryApiClientInterface $apiClient,
        ClientsStorageInterface $clientsStorage,
        SubscriptionsStorageInterface $subscriptionsStorage,
        MatchMakingQueueInterface $matchMakingQueue,
        WebSocketMessageSenderInterface $sender,
    ) {
        $this->apiClient = $apiClient;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->matchMakingQueue = $matchMakingQueue;
        $this->sender = $sender;
    }

    public function create(string $type, object $payload): MessageHandlerInterface
    {
        $channel = $payload->data?->channel ?? '';

        info("Creating message handler for type: $type");

        $requestType = ClientRequestType::tryFrom($type);

        if ($requestType === null) {
            return new UnknownMessageHandler;
        }

        return match ($requestType) {
            ClientRequestType::Auth => new AuthMessageHandler($this->apiClient, $this->clientsStorage),
            ClientRequestType::GuestAuth => new GuestAuthHandler($this->clientsStorage),
            ClientRequestType::Subscribe => new AuthorizedMessageHandler(
                match ($channel) {
                    'matchmaking.queue' => new MatchMakingSubscribeHandler($this->subscriptionsStorage, $this->clientsStorage, $this->matchMakingQueue, $this->sender),
                    default => new SubscribeMessageHandler($this->subscriptionsStorage, $this->clientsStorage),
                },
                $this->clientsStorage
            ),
            ClientRequestType::Unsubscribe => new AuthorizedMessageHandler(
                new UnsubscribeMessageHandler($this->subscriptionsStorage),
                $this->clientsStorage
            ),
            ClientRequestType::MatchmakingJoin => new AuthorizedMessageHandler(
                new MatchMakingJoinHandler($this->clientsStorage, $this->matchMakingQueue, $this->sender),
                $this->clientsStorage
            ),
            ClientRequestType::MatchmakingLeave => new AuthorizedMessageHandler(
                new MatchMakingLeaveHandler($this->clientsStorage, $this->matchMakingQueue, $this->sender),
                $this->clientsStorage
            ),
            ClientRequestType::MatchmakingChallenge => new AuthorizedMessageHandler(
                new MatchMakingChallengeHandler($this->clientsStorage, $this->matchMakingQueue, $this->apiClient, $this->sender),
                $this->clientsStorage
            ),
        };
    }
}
