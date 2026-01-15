<?php

namespace Tests\Unit;

use App\WebSockets\Storage\Timers\MongoTrainingTimerStorage;
use Carbon\Carbon;
use Tests\TestCase;

class MongoTrainingTimerStorageTest extends TestCase
{
    private MongoTrainingTimerStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new MongoTrainingTimerStorage();
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
            $collection->deleteMany(['training_id' => ['$regex' => '^test_']]);
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
        $trainingId = 'test_training_' . time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer($trainingId, $startedAt, $durationSeconds);

        $this->assertTrue($this->storage->hasTimer($trainingId));
    }

    public function test_has_timer_returns_false_for_nonexistent(): void
    {
        $this->assertFalse($this->storage->hasTimer('test_nonexistent_' . time()));
    }

    public function test_get_timer(): void
    {
        $trainingId = 'test_training_' . time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer($trainingId, $startedAt, $durationSeconds);

        $timer = $this->storage->getTimer($trainingId);

        $this->assertNotNull($timer);
        $this->assertEquals($trainingId, $timer['training_id']);
        $this->assertEquals($durationSeconds, $timer['duration_seconds']);
        $this->assertInstanceOf(\DateTime::class, $timer['started_at']);
        $this->assertInstanceOf(\DateTime::class, $timer['expires_at']);
    }

    public function test_get_timer_returns_null_for_nonexistent(): void
    {
        $timer = $this->storage->getTimer('test_nonexistent_' . time());
        $this->assertNull($timer);
    }

    public function test_remove_timer_marks_as_expired(): void
    {
        $trainingId = 'test_training_' . time();
        $startedAt = Carbon::now();
        $durationSeconds = 300;

        $this->storage->addTimer($trainingId, $startedAt, $durationSeconds);
        $this->assertTrue($this->storage->hasTimer($trainingId));

        $this->storage->removeTimer($trainingId);

        $this->assertFalse($this->storage->hasTimer($trainingId));

        // Проверяем что документ существует, но со статусом expired
        $collection = $this->getCollection();
        $document = $collection->findOne(['training_id' => $trainingId]);

        $this->assertNotNull($document);
        $this->assertEquals('expired', $document['status']);
        $this->assertArrayHasKey('expired_at', $document);
    }

    public function test_get_expired_timers(): void
    {
        // Добавляем таймер который уже истек
        $expiredTrainingId = 'test_expired_' . time();
        $expiredStartedAt = Carbon::now()->subMinutes(10);
        $this->storage->addTimer($expiredTrainingId, $expiredStartedAt, 60); // истек 9 минут назад

        // Добавляем активный таймер
        $activeTrainingId = 'test_active_' . time();
        $activeStartedAt = Carbon::now();
        $this->storage->addTimer($activeTrainingId, $activeStartedAt, 300); // истечет через 5 минут

        $expiredTimers = $this->storage->getExpiredTimers();

        $expiredIds = array_column($expiredTimers, 'training_id');

        $this->assertContains($expiredTrainingId, $expiredIds);
        $this->assertNotContains($activeTrainingId, $expiredIds);
    }

    public function test_get_expired_timers_excludes_already_expired_status(): void
    {
        // Добавляем истекший таймер
        $trainingId = 'test_expired_' . time();
        $startedAt = Carbon::now()->subMinutes(10);
        $this->storage->addTimer($trainingId, $startedAt, 60);

        // Проверяем что он в списке истекших
        $expiredTimers = $this->storage->getExpiredTimers();
        $expiredIds = array_column($expiredTimers, 'training_id');
        $this->assertContains($trainingId, $expiredIds);

        // Помечаем как expired
        $this->storage->removeTimer($trainingId);

        // Проверяем что теперь он не в списке истекших
        $expiredTimers = $this->storage->getExpiredTimers();
        $expiredIds = array_column($expiredTimers, 'training_id');
        $this->assertNotContains($trainingId, $expiredIds);
    }

    public function test_update_existing_timer(): void
    {
        $trainingId = 'test_training_' . time();
        $startedAt = Carbon::now();

        // Добавляем таймер на 5 минут
        $this->storage->addTimer($trainingId, $startedAt, 300);

        $timer = $this->storage->getTimer($trainingId);
        $firstExpiresAt = $timer['expires_at'];

        // Обновляем таймер на 10 минут
        $newStartedAt = Carbon::now()->addMinutes(1);
        $this->storage->addTimer($trainingId, $newStartedAt, 600);

        $updatedTimer = $this->storage->getTimer($trainingId);

        $this->assertEquals(600, $updatedTimer['duration_seconds']);
        $this->assertNotEquals($firstExpiresAt->getTimestamp(), $updatedTimer['expires_at']->getTimestamp());
    }
}
