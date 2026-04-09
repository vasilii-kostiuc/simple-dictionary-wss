<?php

namespace App\WebSockets\Handlers\Api\Training;

use App\WebSockets\Handlers\Api\ApiMessageHandlerInterface;
use App\WebSockets\Messages\Training\TrainingCompletedMessage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;

class TrainingCompletedApiHandler implements ApiMessageHandlerInterface
{
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function handle(mixed $payload): void
    {
        $data = $payload['data'] ?? [];
        $trainingId = $data['training_id'] ?? null;

        $connections = $this->subscriptionsStorage->getConnectionsByChannel('training.'.$trainingId);
        $message = new TrainingCompletedMessage($trainingId, $data['completed_at']);

        foreach ($connections as $conn) {
            $conn->send($message);
        }
    }
}
