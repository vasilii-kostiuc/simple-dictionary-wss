<?php

namespace App\Infrastructure\MatchMaking;

use App\Domain\Match\MatchParams;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\MatchMaking\QueueEntry;
use App\Domain\Shared\Identity\ClientIdentity;
use Illuminate\Redis\Connections\Connection;

class RedisMatchMakingQueue implements MatchMakingQueueInterface
{
    private const QUEUE_PREFIX = 'matchmaking:queue:';

    private const USER_DATA_PREFIX = 'matchmaking:user:';

    private const QUEUE_TTL = 3600; // 1 час

    public function __construct(private readonly Connection $redis) {}

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
            'queueKey' => $queueKey,
            'timestamp' => time(),
        ];

        $this->redis->setex($userDataKey, self::QUEUE_TTL, json_encode($stored));
        $this->redis->zadd($queueKey, time(), $identifier);
        $this->redis->expire($queueKey, self::QUEUE_TTL);
    }

    public function remove(string $identifier): void
    {
        $userDataKey = $this->getUserDataKey($identifier);
        $identity = json_decode($this->redis->get($userDataKey), true);

        if ($identity !== null) {
            $queueKey = $this->getQueueKey(MatchParams::fromArray($identity['matchParams']));
            $this->redis->zrem($queueKey, $identifier);
        }

        $this->redis->del($userDataKey);
    }

    public function all(MatchParams $matchParams): array
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = $this->redis->zrange($queueKey, 0, -1);

        $result = [];
        foreach ($identifiers as $identifier) {
            $raw = json_decode($this->redis->get($this->getUserDataKey($identifier)), true);
            if ($raw !== null) {
                $result[] = $this->rawToQueueEntry($raw);
            }
        }

        return $result;
    }

    public function allQueues(): array
    {
        $pattern = self::QUEUE_PREFIX.'*';
        $keys = $this->redis->keys($pattern);

        $result = [];
        foreach ($keys as $queueKey) {
            $identifiers = $this->redis->zrange($queueKey, 0, -1);
            foreach ($identifiers as $identifier) {
                $raw = json_decode($this->redis->get($this->getUserDataKey($identifier)), true);
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

        $result = $this->redis->eval(<<<'LUA'
local queueKey = KEYS[1]
local currentId = ARGV[1]
local userKeyPrefix = ARGV[2]

local members = redis.call('ZRANGE', queueKey, 0, -1)

for _, candidateIdentifier in ipairs(members) do
    if candidateIdentifier ~= currentId then
        local candidateUserData = redis.call('GET', userKeyPrefix .. candidateIdentifier)

        if candidateUserData then
            redis.call('ZREM', queueKey, currentId, candidateIdentifier)
            redis.call('DEL', userKeyPrefix .. currentId)
            redis.call('DEL', userKeyPrefix .. candidateIdentifier)

            return candidateIdentifier
        end

        redis.call('ZREM', queueKey, candidateIdentifier)
    end
end

return false
LUA, 1, $queueKey, $identifier, self::USER_DATA_PREFIX);

        if ($result === false || $result === null) {
            return null;
        }

        return (string) $result;
    }

    public function clear(MatchParams $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $identifiers = $this->redis->zrange($queueKey, 0, -1);

        foreach ($identifiers as $identifier) {
            $this->redis->del($this->getUserDataKey($identifier));
        }

        $this->redis->del($queueKey);
    }

    public function count(MatchParams $matchParams): int
    {
        return (int) $this->redis->zcard($this->getQueueKey($matchParams));
    }

    public function isUserInQueue(string $identifier): bool
    {
        return $this->redis->exists($this->getUserDataKey($identifier)) > 0;
    }

    public function extract(string $identifier): ?QueueEntry
    {
        $userDataKey = $this->getUserDataKey($identifier);

        $raw = $this->redis->eval(<<<'LUA'
local userDataKey = KEYS[1]
local identifier = ARGV[1]
local queuePrefix = ARGV[2]

local raw = redis.call('GET', userDataKey)

if not raw then
    return false
end

local decoded = cjson.decode(raw)
local queueKey = decoded['queueKey']

redis.call('ZREM', queueKey, identifier)
redis.call('DEL', userDataKey)

return raw
LUA, 1, $userDataKey, $identifier, self::QUEUE_PREFIX);

        if ($raw === false || $raw === null) {
            return null;
        }

        return $this->rawToQueueEntry(json_decode($raw, true));
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
