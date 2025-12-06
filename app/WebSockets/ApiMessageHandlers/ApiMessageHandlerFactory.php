<?php

namespace App\WebSockets\ApiMessageHandlers;

use App\WebSockets\Storage\SubscriptionsStorageInterface;

class ApiMessageHandlerFactory
{
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function create(string $type): ApiMessageHandlerInterface
    {
        return match ($type) {
            'training_completed' => new TrainingCompletedApiHandler($this->subscriptionsStorage),
            default => new UnknownApiMessageHandler()
        };
    }
}
