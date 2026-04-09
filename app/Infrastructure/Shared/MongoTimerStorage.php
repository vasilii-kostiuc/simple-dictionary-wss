<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\Contracts\TimerStorageInterface;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

class MongoTimerStorage implements TimerStorageInterface
{
    private Collection $collection;

    public function __construct()
    {
        $mongoHost = env('MONGODB_HOST', 'wss_mongo');
        $mongoPort = env('MONGODB_PORT', 27017);
        $client = new Client("mongodb://{$mongoHost}:{$mongoPort}");

        $this->collection = $client->selectDatabase(env('MONGODB_DATABASE', 'wss_db'))
            ->selectCollection('timers');

        try {
            $this->collection->createIndex(['expires_at' => 1]);
            $this->collection->createIndex(['timer_key' => 1], ['unique' => true]);
        } catch (\Exception $e) {
            Log::warning('Failed to create MongoDB indexes: '.$e->getMessage());
        }
    }

    private function getTimerKey(string $type, string $id): string
    {
        return "{$type}:{$id}";
    }

    public function addTimer(string $type, string $id, Carbon $startedAt, int $durationSeconds): void
    {
        $timerKey = $this->getTimerKey($type, $id);
        $expiresAt = new UTCDateTime(($startedAt->timestamp + $durationSeconds) * 1000);

        $this->collection->updateOne(
            ['timer_key' => $timerKey],
            [
                '$set' => [
                    'timer_key' => $timerKey,
                    'type' => $type,
                    'entity_id' => $id,
                    'started_at' => new UTCDateTime($startedAt->timestamp * 1000),
                    'expires_at' => $expiresAt,
                    'duration_seconds' => $durationSeconds,
                    'status' => 'active',
                    'created_at' => new UTCDateTime(time() * 1000),
                ],
            ],
            ['upsert' => true]
        );

    }

    public function removeTimer(string $type, string $id): void
    {
        $timerKey = $this->getTimerKey($type, $id);

        $result = $this->collection->updateOne(
            ['timer_key' => $timerKey],
            [
                '$set' => [
                    'status' => 'expired',
                    'expired_at' => new UTCDateTime(time() * 1000),
                ],
            ]
        );
    }

    public function getExpiredTimers(): array
    {
        $now = new UTCDateTime(time() * 1000);

        $cursor = $this->collection->find([
            'expires_at' => ['$lte' => $now],
            'status' => 'active',
        ]);

        $timers = [];
        foreach ($cursor as $document) {
            $timers[] = [
                'type' => $document['type'],
                'entity_id' => $document['entity_id'],
                'timer_key' => $document['timer_key'],
                'started_at' => $document['started_at']->toDateTime(),
                'expires_at' => $document['expires_at']->toDateTime(),
                'duration_seconds' => $document['duration_seconds'],
            ];
        }

        return $timers;
    }

    public function hasTimer(string $type, string $id): bool
    {
        $timerKey = $this->getTimerKey($type, $id);

        return $this->collection->countDocuments([
            'timer_key' => $timerKey,
            'status' => 'active',
        ]) > 0;
    }

    public function getTimer(string $type, string $id): ?array
    {
        $timerKey = $this->getTimerKey($type, $id);

        $document = $this->collection->findOne([
            'timer_key' => $timerKey,
            'status' => 'active',
        ]);

        if (! $document) {
            return null;
        }

        return [
            'type' => $document['type'],
            'entity_id' => $document['entity_id'],
            'timer_key' => $document['timer_key'],
            'started_at' => $document['started_at']->toDateTime(),
            'expires_at' => $document['expires_at']->toDateTime(),
            'duration_seconds' => $document['duration_seconds'],
        ];
    }
}
