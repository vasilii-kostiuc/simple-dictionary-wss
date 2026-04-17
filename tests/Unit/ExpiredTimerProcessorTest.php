<?php

namespace Tests\Unit;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\Training\Actions\ProcessExpiredTimersAction;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;
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

        $timer1 = [
            'type' => TimerType::Training->value,
            'entity_id' => 'training-1',
            'expires_at' => Carbon::parse('2026-01-01 10:00:00'),
        ];
        $timer2 = [
            'type' => TimerType::Match->value,
            'entity_id' => 'match-2',
            'expires_at' => Carbon::parse('2026-01-01 10:05:00'),
        ];

        $timerStorage->expects($this->exactly(3))
            ->method('claimExpiredTimer')
            ->willReturnOnConsecutiveCalls($timer1, $timer2, null);

        $apiClient->expects($this->once())
            ->method('expire')
            ->with('training-1');

        $apiClient->expects($this->once())
            ->method('expireMatch')
            ->with('match-2');

        $timerStorage->expects($this->exactly(2))
            ->method('removeTimer')
            ->with(
                $this->callback(fn ($type): bool => in_array($type, [TimerType::Training->value, TimerType::Match->value], true)),
                $this->callback(fn ($id): bool => in_array($id, ['training-1', 'match-2'], true))
            );

        (new ProcessExpiredTimersAction($timerStorage, $apiClient))->execute();
    }

    public function test_process_does_nothing_when_no_timers_are_expired(): void
    {
        $timerStorage = $this->createMock(TimerStorageInterface::class);
        $apiClient = $this->createMock(SimpleDictionaryApiClientInterface::class);

        $timerStorage->expects($this->once())->method('claimExpiredTimer')->willReturn(null);
        $apiClient->expects($this->never())->method('expire');
        $apiClient->expects($this->never())->method('expireMatch');
        $timerStorage->expects($this->never())->method('removeTimer');

        (new ProcessExpiredTimersAction($timerStorage, $apiClient))->execute();
    }
}
