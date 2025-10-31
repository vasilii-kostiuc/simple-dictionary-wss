<?php

namespace App\WebSockets\Handlers;

use GuzzleHttp\Client;

class MessageHandlerFactory
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(string $type): MessageHandlerInterface
    {
        return match ($type) {
            'auth' => new AuthMessageHandler($this->client),
            default => new UnknownMessageHandler()
        };
    }
}
