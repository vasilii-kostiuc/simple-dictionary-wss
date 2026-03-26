<?php

namespace App\WebSockets\Handlers\Api;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use React\EventLoop\LoopInterface;
use App\WebSockets\Handlers\Api\Training\TrainingStartHandler;
use App\WebSockets\Handlers\Api\Training\TrainingCompletedApiHandler;
use App\WebSockets\Handlers\Api\Match\MatchCreatedHandler;
use App\WebSockets\Handlers\Api\Match\MatchStartedHandler;
use App\WebSockets\Handlers\Api\Match\MatchStepGeneratedHandler;
use App\WebSockets\Handlers\Api\Match\MatchCompletedHandler;
use App\WebSockets\Handlers\Api\Match\MatchSummaryHandler;
use App\WebSockets\Handlers\Api\UnknownApiMessageHandler;

class ApiMessageHandlerFactory
{
    public function __construct(
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
        private readonly LoopInterface $loop,
        private readonly SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
        private readonly ClientsStorageInterface $clientsStorage,
        private readonly TimerStorageInterface $trainingTimerStorage
    ) {
    }

    public function create(string $type): ApiMessageHandlerInterface
    {
        return match ($type) {
            'training_started' => new TrainingStartHandler($this->loop, $this->trainingTimerStorage, $this->simpleDictionaryApiClient),
            'training_completed' => new TrainingCompletedApiHandler($this->subscriptionsStorage),
            'match_created' => new MatchCreatedHandler($this->clientsStorage),
            'match_started' => new MatchStartedHandler($this->loop, $this->trainingTimerStorage, $this->clientsStorage),
            'next_step_generated' => new MatchStepGeneratedHandler($this->clientsStorage),
            'match_summary' => new MatchSummaryHandler($this->clientsStorage),
            'match_completed' => new MatchCompletedHandler($this->clientsStorage),
            default => new UnknownApiMessageHandler
        };
    }
}
