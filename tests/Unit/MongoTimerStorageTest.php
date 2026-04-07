<?php

namespace Tests\Unit;

use App\WebSockets\Enums\TimerType;
use App\WebSockets\Storage\Timers\MongoTimerStorage;
use Carbon\Carbon;
use Tests\TestCase;

class MongoTimerStorageTest extends TestCase
{
    private MongoTimerStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // $mongoHost = env('MONGODB_HOST', 'wss_mongo');
        // $mongoPort = env('MONGODB_PORT', 27017);

        // $socket = @fsockopen($mongoHost, (int) $mongoPort, $errno, $errstr, 1);
        // if ($socket === false) {
        //     $this->markTestSkipped("MongoDB недоступна ({$mongoHost}:{$mongoPort}): {$errstr}");
        // }
        // fclose($socket);

        $this->storage = new MongoTimerStorage;
        $this->cleanupTimers();
    }

    protected function tearDown(): void
    {
        $this->cleanupTimers();
        parent::tearDown();
    }

    private function cleanupTimers(): void
    {
        try {
            $reflection = new \ReflectionClass($this->storage);
            $property = $reflection->getProperty('collection');
            $property->setAccessible(true);
            $collection = $property->getValue($this->storage);
            $collection->deleteMany(['timer_key' => ['$regex' => '^test_']]);
        } catch (\Exception $e) {
            // Игнорируем ошибки при очистке
        }
    }

    private function getCollection()
    {
        $reflection = new \ReflectionClass($this->storage);
        $property = $reflection->getProperty('collection');
        $property->setAccessible(true);

        return $property->getValue($this->storage);
    }

    public function test_add_timer(): void
    {
        $trainingId = 'test_training_'.time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);

        $this->assertTrue($this->storage->hasTimer(TimerType::Training->value, $trainingId));
    }

    public function test_has_timer_returns_false_for_nonexistent(): void
    {
        $this->assertFalse($this->storage->hasTimer(TimerType::Training->value, 'test_nonexistent_'.time()));
    }

    public function test_get_timer(): void
    {
        $trainingId = 'test_training_'.time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);

        $timer = $this->storage->getTimer(TimerType::Training->value, $trainingId);

        $this->assertNotNull($timer);
        $this->assertEquals(TimerType::Training->value, $timer['type']);
        $this->assertEquals($trainingId, $timer['entity_id']);
        $this->assertEquals($durationSeconds, $timer['duration_seconds']);
        $this->assertInstanceOf(\DateTime::class, $timer['started_at']);
        $this->assertInstanceOf(\DateTime::class, $timer['expires_at']);
    }

    public function test_get_timer_returns_null_for_nonexistent(): void
    {
        $timer = $this->storage->getTimer(TimerType::Training->value, 'test_nonexistent_'.time());
        $this->assertNull($timer);
    }

    public function test_remove_timer_marks_as_expired(): void
    {
        $trainingId = 'test_training_'.time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer(TimerType::Training->value, $trainingId, $startedAt, $durationSeconds);
        $this->assertTrue($this->storage->hasTimer(TimerType::Training->value, $trainingId));

        $this->storage->removeTimer(TimerType::Training->value, $trainingId);

        $this->assertFalse($this->storage->hasTimer(TimerType::Training->value, $trainingId));

        // Проверяем что документ существует, но со статусом expired
        $collection = $this->getCollection();
        $timerKey = TimerType::Training->value.':'.$trainingId;
        $document = $collection->findOne(['timer_key' => $timerKey]);

        $this->assertNotNull($document);
        $this->assertEquals('expired', $document['status']);
        $this->assertArrayHasKey('expired_at', $document);
    }

    public function test_get_expired_timers(): void
    {
        // Добавляем таймер который уже истек
        $expiredTrainingId = 'test_expired_'.time();
        $expiredStartedAt = Carbon::now()->subMinutes(10);
        $this->storage->addTimer(TimerType::Training->value, $expiredTrainingId, $expiredStartedAt, 60); // истек 9 минут назад

        // Добавляем активный таймер
        $activeTrainingId = 'test_active_'.time();
        $activeStartedAt = Carbon::now();
        $this->storage->addTimer(TimerType::Training->value, $activeTrainingId, $activeStartedAt, 300); // истечет через 5 минут

        $expiredTimers = $this->storage->getExpiredTimers();

        $expiredIds = array_column($expiredTimers, 'entity_id');

        $this->assertContains($expiredTrainingId, $expiredIds);
        $this->assertNotContains($activeTrainingId, $expiredIds);
    }

    public function test_get_expired_timers_excludes_already_expired_status(): void
    {
        // Добавляем истекший таймер
        $trainingId = 'test_expired_'.time();
        $startedAt = Carbon::now()->subMinutes(10);
        $this->storage->addTimer(TimerType::Training->value, $trainingId, $startedAt, 60);

        // Проверяем что он в списке истекших
        $expiredTimers = $this->storage->getExpiredTimers();
        $expiredIds = array_column($expiredTimers, 'entity_id');
        $this->assertContains($trainingId, $expiredIds);

        // Помечаем как expired
        $this->storage->removeTimer(TimerType::Training->value, $trainingId);

        // Проверяем что теперь он не в списке истекших
        $expiredTimers = $this->storage->getExpiredTimers();
        $expiredIds = array_column($expiredTimers, 'entity_id');
        $this->assertNotContains($trainingId, $expiredIds);
    }

    public function test_update_existing_timer(): void
    {
        $trainingId = 'test_training_'.time();
        $startedAt = Carbon::now();

        // Добавляем таймер на 5 минут
        $this->storage->addTimer(TimerType::Training->value, $trainingId, $startedAt, 300);

        $timer = $this->storage->getTimer(TimerType::Training->value, $trainingId);
        $firstExpiresAt = $timer['expires_at'];

        // Обновляем таймер на 10 минут
        $newStartedAt = Carbon::now()->addMinutes(1);
        $this->storage->addTimer(TimerType::Training->value, $trainingId, $newStartedAt, 600);

        $updatedTimer = $this->storage->getTimer(TimerType::Training->value, $trainingId);

        $this->assertEquals(600, $updatedTimer['duration_seconds']);
        $this->assertNotEquals($firstExpiresAt->getTimestamp(), $updatedTimer['expires_at']->getTimestamp());
    }

    public function test_match_timer(): void
    {
        $matchId = 'test_match_'.time();
        $startedAt = Carbon::now();
        $durationSeconds = 600;

        $this->storage->addTimer(TimerType::Match ->value, $matchId, $startedAt, $durationSeconds);

        $this->assertTrue($this->storage->hasTimer(TimerType::Match ->value, $matchId));

        $timer = $this->storage->getTimer(TimerType::Match ->value, $matchId);
        $this->assertNotNull($timer);
        $this->assertEquals(TimerType::Match ->value, $timer['type']);
        $this->assertEquals($matchId, $timer['entity_id']);
    }
}
