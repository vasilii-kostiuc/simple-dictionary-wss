<?php

namespace App\Infrastructure\Shared;

use App\Application\Contracts\LockManagerInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class CacheLockManager implements LockManagerInterface
{
    public function __construct(
        private readonly CacheFactory $cache,
    ) {}

    public function execute(string $key, callable $callback): mixed
    {
        $store = (string) config('locks.store', 'redis');
        $prefix = (string) config('locks.prefix', 'lock');
        $ttlSeconds = (int) config('locks.ttl_seconds', 5);
        $waitSeconds = (int) config('locks.wait_seconds', 1);

        return $this->cache
            ->store($store)
            ->lock($prefix.':'.$key, $ttlSeconds)
            ->block($waitSeconds, $callback);
    }
}
