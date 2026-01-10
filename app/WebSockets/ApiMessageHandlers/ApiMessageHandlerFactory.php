<?php

namespace App\WebSockets\ApiMessageHandlers;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use React\EventLoop\LoopInterface;
use App\WebSockets\Storage\Timers\TrainingTimerStorageInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class ApiMessageHandlerFactory
{
    private SubscriptionsStorageInterface $subscriptionsStorage;
    private LoopInterface $loop;

    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    private TrainingTimerStorageInterface $trainingTimerStorage;

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage, LoopInterface $loop, SimpleDictionaryApiClientInterface $simpleDictionaryApiClient, TrainingTimerStorageInterface $timerStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->loop = $loop;
        $this->simpleDictionaryApiClient = $simpleDictionaryApiClient;
        $this->trainingTimerStorage = $timerStorage;
    }

    public function create(
        string $type
    ): ApiMessageHandlerInterface {
        return match ($type) {
            'training_start' => new TrainingStartHandler($this->loop, $this->trainingTimerStorage, $this->simpleDictionaryApiClient),
            'training_completed' => new TrainingCompletedApiHandler($this->subscriptionsStorage),
            default => new UnknownApiMessageHandler()
        };
    }
}
