<?php

namespace App\WebSockets\Storage\MatchMaking;

use App\WebSockets\DTO\UserData;
use Illuminate\Support\Facades\Redis;

class RedisMatchMakingQueue implements MatchMakingQueueInterface
{
    private const QUEUE_PREFIX = 'matchmaking:queue:';

    private const USER_DATA_PREFIX = 'matchmaking:user:';

    private const QUEUE_TTL = 3600; // 1 час

    public function add(UserData $userData, array $matchParams): void
    {
        $this->remove($userData->id, $matchParams);

        $queueKey = $this->getQueueKey($matchParams);
        $userDataKey = $this->getUserDataKey($userData->id);

        $stored = [
            'userId' => $userData->id,
            'name' => $userData->name,
            'email' => $userData->email,
            'avatar' => $userData->avatar,
            'matchParams' => $matchParams,
            'timestamp' => time(),
        ];

        Redis::setex($userDataKey, self::QUEUE_TTL, json_encode($stored));
        Redis::zadd($queueKey, time(), $userData->id);
        Redis::expire($queueKey, self::QUEUE_TTL);
    }

    public function remove(int $userId, array $matchParams = []): void
    {
        $userDataKey = $this->getUserDataKey($userId);
        $userData = json_decode(Redis::get($userDataKey), true);

        if ($userData !== null) {
            $queueKey = $this->getQueueKey($userData['matchParams']);
            Redis::zrem($queueKey, $userId);
        }

        Redis::del($userDataKey);
    }

    public function all(array $matchParams): array
    {
        $queueKey = $this->getQueueKey($matchParams);
        $userIds = Redis::zrange($queueKey, 0, -1);

        $result = [];
        foreach ($userIds as $userId) {
            $raw = json_decode(Redis::get($this->getUserDataKey($userId)), true);
            if ($raw !== null) {
                $result[] = [
                    'userId' => $raw['userId'],
                    'name' => $raw['name'],
                    'email' => $raw['email'],
                    'avatar' => $raw['avatar'],
                ];
            }
        }

        return $result;
    }

    public function allQueues(): array
    {
        $pattern = self::QUEUE_PREFIX.'*';
        $keys = Redis::keys($pattern);

        $result = [];
        foreach ($keys as $queueKey) {
            $userIds = Redis::zrange($queueKey, 0, -1);
            foreach ($userIds as $userId) {
                $raw = json_decode(Redis::get($this->getUserDataKey($userId)), true);
                if ($raw !== null) {
                    $result[] = [
                        'userId' => $raw['userId'],
                        'name' => $raw['name'],
                        'email' => $raw['email'],
                        'avatar' => $raw['avatar'],
                        'matchParams' => $raw['matchParams'],
                    ];
                }
            }
        }

        return $result;
    }

    public function findMatch(int $userId, array $matchParams): ?int
    {
        $queueKey = $this->getQueueKey($matchParams);
        $users = Redis::zrange($queueKey, 0, -1);

        foreach ($users as $candidateUserId) {
            if ((int) $candidateUserId !== $userId) {
                Redis::zrem($queueKey, $userId, $candidateUserId);
                Redis::del($this->getUserDataKey($userId));
                Redis::del($this->getUserDataKey($candidateUserId));

                return (int) $candidateUserId;
            }
        }

        return null;
    }

    public function clear(array $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $users = Redis::zrange($queueKey, 0, -1);

        foreach ($users as $userId) {
            Redis::del($this->getUserDataKey($userId));
        }

        Redis::del($queueKey);
    }

    public function count(array $matchParams): int
    {
        return (int) Redis::zcard($this->getQueueKey($matchParams));
    }

    private function getQueueKey(array $matchParams): string
    {
        ksort($matchParams);

        return self::QUEUE_PREFIX.md5(json_encode($matchParams));
    }

    private function getUserDataKey(int $userId): string
    {
        return self::USER_DATA_PREFIX.$userId;
    }
}
