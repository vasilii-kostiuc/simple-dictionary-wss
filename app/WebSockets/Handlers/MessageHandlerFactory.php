<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Api\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\ClientsStorageInterface;
use App\WebSockets\Storage\SubscriptionsStorageInterface;
use GuzzleHttp\Client;

class MessageHandlerFactory
{
    private SimpleDictionaryApiClientInterface $apiClient;
    private ClientsStorageInterface $clientsStorage;
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(SimpleDictionaryApiClientInterface $apiClient, ClientsStorageInterface $clientsStorage, SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->apiClient = $apiClient;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function create(string $type): MessageHandlerInterface
    {
        return match ($type) {
            'auth' => new AuthMessageHandler($this->apiClient, $this->clientsStorage),
            'subscribe' => new SubscribeMessageHandler($this->subscriptionsStorage, $this->clientsStorage),
            default => new UnknownMessageHandler()
        };
    }
}
