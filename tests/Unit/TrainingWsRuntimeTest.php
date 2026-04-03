<?php

namespace Tests\Unit;

use App\WebSockets\Broker\BrokerSubscriptionsBootstrapper;
use App\WebSockets\Timers\PeriodicTimerScheduler;
use App\WebSockets\TrainingWsRuntime;
use App\WebSockets\TrainingWsServer;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class TrainingWsRuntimeTest extends TestCase
{
    public function test_bootstrap_creates_broker_subscriptions_and_starts_scheduler(): void
    {
        $trainingWsServer = $this->createMock(TrainingWsServer::class);
        $bootstrapper = $this->createMock(BrokerSubscriptionsBootstrapper::class);
        $scheduler = $this->createMock(PeriodicTimerScheduler::class);
        $messageBrokerFactory = $this->createMock(MessageBrokerFactory::class);
        $messageBroker = $this->createMock(MessageBrokerInterface::class);
        $loop = $this->createMock(LoopInterface::class);

        $messageBrokerFactory->expects($this->once())
            ->method('create')
            ->willReturn($messageBroker);

        $bootstrapper->expects($this->once())
            ->method('bootstrap')
            ->with($messageBroker);

        $scheduler->expects($this->once())
            ->method('start');

        $runtime = new TrainingWsRuntime(
            $trainingWsServer,
            $bootstrapper,
            $scheduler,
            $messageBrokerFactory,
            $loop,
        );

        $runtime->bootstrap();
    }

    public function test_bootstrap_is_idempotent(): void
    {
        $trainingWsServer = $this->createMock(TrainingWsServer::class);
        $bootstrapper = $this->createMock(BrokerSubscriptionsBootstrapper::class);
        $scheduler = $this->createMock(PeriodicTimerScheduler::class);
        $messageBrokerFactory = $this->createMock(MessageBrokerFactory::class);
        $messageBroker = $this->createMock(MessageBrokerInterface::class);
        $loop = $this->createMock(LoopInterface::class);

        $messageBrokerFactory->expects($this->once())
            ->method('create')
            ->willReturn($messageBroker);

        $bootstrapper->expects($this->once())
            ->method('bootstrap')
            ->with($messageBroker);

        $scheduler->expects($this->once())
            ->method('start');

        $runtime = new TrainingWsRuntime(
            $trainingWsServer,
            $bootstrapper,
            $scheduler,
            $messageBrokerFactory,
            $loop,
        );

        $runtime->bootstrap();
        $runtime->bootstrap();
    }
}
