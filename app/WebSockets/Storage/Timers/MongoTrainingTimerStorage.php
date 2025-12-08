<?php

namespace App\WebSockets\Storage\Timers;

use Illuminate\Support\Facades\Log;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use Carbon\Carbon;

class MongoTrainingTimerStorage implements TrainingTimerStorageInterface
{
    private Collection $collection;

    public function __construct()
    {
        $mongoHost = env('MONGODB_HOST', 'wss_mongo');
        $mongoPort = env('MONGODB_PORT', 27017);
        $client = new Client("mongodb://{$mongoHost}:{$mongoPort}");

        $this->collection = $client->selectDatabase(env('MONGODB_DATABASE', 'wss_db'))
            ->selectCollection('training_timers');

        try {
            $this->collection->createIndex(['expires_at' => 1]);
            $this->collection->createIndex(['training_id' => 1], ['unique' => true]);
        } catch (\Exception $e) {
            Log::warning('Failed to create MongoDB indexes: ' . $e->getMessage());
        }
    }
    public function addTimer(string $trainingId, Carbon $startedAt, int $durationSeconds): void
    {
        $expiresAt = new UTCDateTime(($startedAt->timestamp + $durationSeconds) * 1000);

        $this->collection->updateOne(
            ['training_id' => $trainingId],
            [
                '$set' => [
                    'training_id' => $trainingId,
                    'started_at' => new UTCDateTime($startedAt->timestamp * 1000),
                    'expires_at' => $expiresAt,
                    'duration_seconds' => $durationSeconds,
                    'status' => 'active',
                    'created_at' => new UTCDateTime(time() * 1000),
                ]
            ],
            ['upsert' => true]
        );

        Log::info("Timer added to MongoDB", [
            'training_id' => $trainingId,
            'duration' => $durationSeconds,
            'expires_at' => date('Y-m-d H:i:s', time() + $durationSeconds)
        ]);
    }

    public function removeTimer(string $trainingId): void
    {
        $result = $this->collection->updateOne(
            ['training_id' => $trainingId],
            [
                '$set' => [
                    'status' => 'expired',
                    'expired_at' => new UTCDateTime(time() * 1000),
                ]
            ]
        );

        Log::info("Timer marked as expired in MongoDB", [
            'training_id' => $trainingId,
            'modified_count' => $result->getModifiedCount()
        ]);
    }
    public function getExpiredTimers(): array
    {
        $now = new UTCDateTime(time() * 1000);

        $cursor = $this->collection->find([
            'expires_at' => ['$lte' => $now],
            'status' => 'active'
        ]);

        $timers = [];
        foreach ($cursor as $document) {
            $timers[] = [
                'training_id' => $document['training_id'],
                'started_at' => $document['started_at']->toDateTime(),
                'expires_at' => $document['expires_at']->toDateTime(),
                'duration_seconds' => $document['duration_seconds']
            ];
        }

        return $timers;
    }

    public function hasTimer(string $trainingId): bool
    {
        return $this->collection->countDocuments([
            'training_id' => $trainingId,
            'status' => 'active'
        ]) > 0;
    }

    public function getTimer(string $trainingId): ?array
    {
        $document = $this->collection->findOne([
            'training_id' => $trainingId,
            'status' => 'active'
        ]);

        if (!$document) {
            return null;
        }

        return [
            'training_id' => $document['training_id'],
            'started_at' => $document['started_at']->toDateTime(),
            'expires_at' => $document['expires_at']->toDateTime(),
            'duration_seconds' => $document['duration_seconds']
        ];
    }
}
