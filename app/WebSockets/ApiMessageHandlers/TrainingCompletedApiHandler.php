<?php

namespace App\WebSockets\ApiMessageHandlers;

use App\WebSockets\Messages\TrainingCompletedMessage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;

class TrainingCompletedApiHandler implements ApiMessageHandlerInterface
{
    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function handle(string $channel, mixed $data): void
    {
        Log::info('Training completed broker message received', [
            'channel' => $channel,
            'data' => $data
        ]);

        $trainingId = $data['training_id'] ?? null;

        $count = $this->subscriptionsStorage->countByChannel("training.$trainingId");
        Log::info("Current number of subscriptions is $count");

        $connections = $this->subscriptionsStorage->getConnectionsByChannel('training.' . $trainingId);
        $message = new TrainingCompletedMessage($trainingId, $data['completed_at']);

        foreach ($connections as $connectionId => $conn) {
            Log::info('Sending training completed message to connection', ['connection_id' => $connectionId]);
            $conn->send($message);
        }

        Log::info('Training completed message sent to ' . count($connections) . ' clients');
    }
}
