<?php

namespace App\WebSockets\Handlers\Api;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use React\EventLoop\LoopInterface;
use App\WebSockets\Handlers\Api\Training\TrainingStartHandler;
use App\WebSockets\Handlers\Api\Training\TrainingCompletedApiHandler;
use App\WebSockets\Handlers\Api\Match\MatchCreatedHandler;
use App\WebSockets\Handlers\Api\Match\MatchStartedHandler;
use App\WebSockets\Handlers\Api\UnknownApiMessageHandler;

class ApiMessageHandlerFactory
{
    private SubscriptionsStorageInterface $subscriptionsStorage;

    private LoopInterface $loop;

    private SimpleDictionaryApiClientInterface $simpleDictionaryApiClient;

    private TimerStorageInterface $trainingTimerStorage;

    public function __construct(
        SubscriptionsStorageInterface $subscriptionsStorage,
        LoopInterface $loop,
        SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        TimerStorageInterface $timerStorage
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
