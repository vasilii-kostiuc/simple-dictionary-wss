<?php

namespace Tests\Unit;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use App\WebSockets\Timers\ExpiredTimerProcessor;
use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class ExpiredTimerProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('log', new class
        {
            public function info(...$args): void {}

            public function warning(...$args): void {}

            public function error(...$args): void {}
        });

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_process_expires_each_due_timer_and_removes_it(): void
    {
        $timerStorage = $this->createMock(TimerStorageInterface::class);
        $apiClient = $this->createMock(SimpleDictionaryApiClientInterface::class);

        $expiredTimers = [
            [
                'type' => 'training',
                'entity_id' => 'training-1',
                'expires_at' => Carbon::parse('2026-01-01 10:00:00'),
            ],
            [
                'type' => 'match',
                'entity_id' => 'match-2',
                'expires_at' => Carbon::parse('2026-01-01 10:05:00'),
            ],
        ];

        $timerStorage->expects($this->once())->method('getExpiredTimers')->willReturn($expiredTimers);

        $apiClient->expects($this->exactly(2))
            ->method('expire')
            ->with($this->callback(fn ($id): bool => in_array($id, ['training-1', 'match-2'], true)));

        $timerStorage->expects($this->exactly(2))
            ->method('removeTimer')
            ->with($this->callback(fn ($type): bool => in_array($type, ['training', 'match'], true)), $this->callback(fn ($id): bool => in_array($id, ['training-1', 'match-2'], true)));

        (new ExpiredTimerProcessor($timerStorage, $apiClient))->process();
    }

    public function test_process_does_nothing_when_no_timers_are_expired(): void
    {
        $timerStorage = $this->createMock(TimerStorageInterface::class);
        $apiClient = $this->createMock(SimpleDictionaryApiClientInterface::class);

        $timerStorage->expects($this->once())->method('getExpiredTimers')->willReturn([]);
        $apiClient->expects($this->never())->method('expire');
        $timerStorage->expects($this->never())->method('removeTimer');

        (new ExpiredTimerProcessor($timerStorage, $apiClient))->process();
    }
}
