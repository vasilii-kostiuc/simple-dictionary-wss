<?php

namespace App\WebSockets\Handlers\Api;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Storage\Timers\TrainingTimerStorageInterface;
use React\EventLoop\LoopInterface;

class ApiMessageHandlerFactory
{
    private SubscriptionsStorageInterface $subscriptionsStorage;

    private LoopInterface $loop;

    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    private TrainingTimerStorageInterface $trainingTimerStorage;

    public function __construct(
        SubscriptionsStorageInterface $subscriptionsStorage,
        LoopInterface $loop,
        SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        TrainingTimerStorageInterface $timerStorage
    ) {
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->loop = $loop;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
        $this->trainingTimerStorage = $timerStorage;
    }

    public function create(string $type): ApiMessageHandlerInterface
    {
        return match ($type) {
            'training_started' => new TrainingStartHandler($this->loop, $this->trainingTimerStorage, $this->simpleDictionaryApiClient),
            'training_completed' => new TrainingCompletedApiHandler($this->subscriptionsStorage),
            default => new UnknownApiMessageHandler
        };
    }
}
