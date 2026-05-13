<?php

namespace App\Infrastructure\Shared;

use App\Application\Contracts\LockManagerInterface;
use Illuminate\Redis\Connections\Connection;

class RedisLockManager implements LockManagerInterface
{
    public function __construct(private readonly Connection $redis) {}

    public function execute(string $key, callable $callback): mixed
    {
        $lockKey = 'lock:'.$key;
        $lockValue = bin2hex(random_bytes(16));
        $acquired = false;

        for ($i = 0; $i < 20; $i++) {
            if ($this->redis->set($lockKey, $lockValue, 'EX', 5, 'NX')) {
                $acquired = true;
                break;
            }
            usleep(50_000);
        }

        if (! $acquired) {
            throw new \RuntimeException("Failed to acquire lock: {$key}");
        }

        try {
            return $callback();
        } finally {
            $this->redis->command('eval', [
                "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end",
                1,
                $lockKey,
                $lockValue,
            ]);
        }
    }
}
