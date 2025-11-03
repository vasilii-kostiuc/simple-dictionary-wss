<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Storage\ClientsStorageInterface;
use App\WebSockets\Storage\SubscriptionsStorageInterface;
use GuzzleHttp\Client;

class MessageHandlerFactory
{
    private Client $client;
    private ClientsStorageInterface $clientsStorage;
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(Client $client, ClientsStorageInterface $clientsStorage, SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->client = $client;
        $this->clientsStorage = $clientsStorage;
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function create(string $type): MessageHandlerInterface
    {
        return match ($type) {
            'auth' => new AuthMessageHandler($this->client, $this->clientsStorage),
            'subscribe' => new SubscribeMessageHandler($this->subscriptionsStorage, $this->clientsStorage),
            default => new UnknownMessageHandler()
        };
    }
}
