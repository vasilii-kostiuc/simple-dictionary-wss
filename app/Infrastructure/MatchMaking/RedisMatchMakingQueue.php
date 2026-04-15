<?php

namespace App\Infrastructure\MatchMaking;

use App\Domain\Match\MatchParams;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\MatchMaking\QueueEntry;
use App\Domain\Shared\Identity\ClientIdentity;
use Illuminate\Support\Facades\Redis;

class RedisMatchMakingQueue implements MatchMakingQueueInterface
{
    private const QUEUE_PREFIX = 'matchmaking:queue:';

    private const USER_DATA_PREFIX = 'matchmaking:user:';

    private const QUEUE_TTL = 3600; // 1 час

    public function add(ClientIdentity $identity, MatchParams $matchParams): void
    {
        $identifier = $identity->getIdentifier();
        $this->remove($identifier);

        $queueKey = $this->getQueueKey($matchParams);
        $userDataKey = $this->getUserDataKey($identifier);

        $stored = [
            'userId' => $identity->id,
            'guestId' => $identity->guestId,
            'identifier' => $identifier,
            'name' => $identity->name,
            'email' => $identity->email,
            'avatar' => $identity->avatar,
            'matchParams' => $matchParams->toArray(),
            'timestamp' => time(),
        ];

        Redis::setex($userDataKey, self::QUEUE_TTL, json_encode($stored));
        Redis::zadd($queueKey, time(), $identifier);
        Redis::expire($queueKey, self::QUEUE_TTL);
    }

    public function remove(string $identifier): void
    {
        $userDataKey = $this->getUserDataKey($identifier);
        $identity = json_decode(Redis::get($userDataKey), true);

        if ($identity !== null) {
            $queueKey = $this->getQueueKey(MatchParams::fromArray($identity['matchParams']));
            Redis::zrem($queueKey, $identifier);
        }

        Redis::del($userDataKey);
    }

    public function all(MatchParams $matchParams): array
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = Redis::zrange($queueKey, 0, -1);

        $result = [];
        foreach ($identifiers as $identifier) {
            $raw = json_decode(Redis::get($this->getUserDataKey($identifier)), true);
            if ($raw !== null) {
                $result[] = $this->rawToQueueEntry($raw);
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
                    $result[] = $this->rawToQueueEntry($raw);
                }
            }
        }

        return $result;
    }

    public function findMatch(string $identifier, MatchParams $matchParams): ?string
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

    public function clear(MatchParams $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = Redis::zrange($queueKey, 0, -1);

        foreach ($identifiers as $identifier) {
            Redis::del($this->getUserDataKey($identifier));
        }

        Redis::del($queueKey);
    }

    public function count(MatchParams $matchParams): int
    {
        return (int) Redis::zcard($this->getQueueKey($matchParams));
    }

    public function isUserInQueue(string $identifier): bool
    {
        return Redis::exists($this->getUserDataKey($identifier)) > 0;
    }

    public function extract(string $identifier): ?QueueEntry
    {
        $userDataKey = $this->getUserDataKey($identifier);
        $raw = json_decode(Redis::get($userDataKey), true);

        if ($raw === null) {
            return null;
        }

        $this->remove($identifier);

        return $this->rawToQueueEntry($raw);
    }

    private function rawToQueueEntry(array $raw): QueueEntry
    {
        return new QueueEntry(
            identity: new ClientIdentity(
                id: $raw['userId'],
                name: $raw['name'],
                email: $raw['email'],
                avatar: $raw['avatar'],
                guestId: $raw['guestId'] ?? null,
            ),
            matchParams: MatchParams::fromArray($raw['matchParams']),
        );
    }

    private function getQueueKey(MatchParams $matchParams): string
    {
        $key = $matchParams->toArray();
        ksort($key);

        return self::QUEUE_PREFIX.md5(json_encode($key));
    }

    private function getUserDataKey(string $identifier): string
    {
        return self::USER_DATA_PREFIX.$identifier;
    }
}
