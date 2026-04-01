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
        $identifier = $userData->getIdentifier();
        $this->remove($identifier, $matchParams);

        $queueKey = $this->getQueueKey($matchParams);
        $userDataKey = $this->getUserDataKey($identifier);

        $stored = [
            'userId' => $userData->id,
            'guestId' => $userData->guestId,
            'identifier' => $identifier,
            'name' => $userData->name,
            'email' => $userData->email,
            'avatar' => $userData->avatar,
            'matchParams' => $matchParams,
            'timestamp' => time(),
        ];

        Redis::setex($userDataKey, self::QUEUE_TTL, json_encode($stored));
        Redis::zadd($queueKey, time(), $identifier);
        Redis::expire($queueKey, self::QUEUE_TTL);
    }

    public function remove(string $identifier, array $matchParams = []): void
    {
        $userDataKey = $this->getUserDataKey($identifier);
        $userData = json_decode(Redis::get($userDataKey), true);

        if ($userData !== null) {
            $queueKey = $this->getQueueKey($userData['matchParams']);
            Redis::zrem($queueKey, $identifier);
        }

        Redis::del($userDataKey);
    }

    public function all(array $matchParams): array
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = Redis::zrange($queueKey, 0, -1);

        $result = [];
        foreach ($identifiers as $identifier) {
            $raw = json_decode(Redis::get($this->getUserDataKey($identifier)), true);
            if ($raw !== null) {
                $result[] = [
                    'userId' => $raw['userId'],
                    'guestId' => $raw['guestId'] ?? null,
                    'identifier' => $raw['identifier'],
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
            $identifiers = Redis::zrange($queueKey, 0, -1);
            foreach ($identifiers as $identifier) {
                $raw = json_decode(Redis::get($this->getUserDataKey($identifier)), true);
                if ($raw !== null) {
                    $result[] = [
                        'userId' => $raw['userId'],
                        'guestId' => $raw['guestId'] ?? null,
                        'identifier' => $raw['identifier'],
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

    public function findMatch(string $identifier, array $matchParams): ?string
    {
        $queueKey = $this->getQueueKey($matchParams);
        $members = Redis::zrange($queueKey, 0, -1);

        foreach ($members as $candidateIdentifier) {
            if ($candidateIdentifier !== $identifier) {
                Redis::zrem($queueKey, $identifier, $candidateIdentifier);
                Redis::del($this->getUserDataKey($identifier));
                Redis::del($this->getUserDataKey($candidateIdentifier));

                return $candidateIdentifier;
            }
        }

        return null;
    }

    public function clear(array $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = Redis::zrange($queueKey, 0, -1);

        foreach ($identifiers as $identifier) {
            Redis::del($this->getUserDataKey($identifier));
        }

        Redis::del($queueKey);
    }

    public function count(array $matchParams): int
    {
        return (int) Redis::zcard($this->getQueueKey($matchParams));
    }

    public function isUserInQueue(string $identifier): bool
    {
        return Redis::exists($this->getUserDataKey($identifier)) > 0;
    }

    public function extract(string $identifier): ?array
    {
        $userDataKey = $this->getUserDataKey($identifier);
        $userData = json_decode(Redis::get($userDataKey), true);

        if ($userData === null) {
            return null;
        }

        $this->remove($identifier);

        return [
            'userId' => $userData['userId'],
            'guestId' => $userData['guestId'] ?? null,
            'identifier' => $userData['identifier'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'avatar' => $userData['avatar'],
            'matchParams' => $userData['matchParams'],
        ];
    }

    private function getQueueKey(array $matchParams): string
    {
        ksort($matchParams);

        return self::QUEUE_PREFIX.md5(json_encode($matchParams));
    }

    private function getUserDataKey(string $identifier): string
    {
        return self::USER_DATA_PREFIX.$identifier;
    }
}
