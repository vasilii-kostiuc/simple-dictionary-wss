<?php

namespace App\Infrastructure\Shared;

use App\Application\Contracts\LockManagerInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class CacheLockManager implements LockManagerInterface
{
    private readonly string $store;

    private readonly string $prefix;

    private readonly int $ttlSeconds;

    private readonly int $waitSeconds;

    public function __construct(
        private readonly CacheFactory $cache,
    ) {
        $this->store = (string) config('locks.store', 'redis');
        $this->prefix = (string) config('locks.prefix', 'lock');
        $this->ttlSeconds = (int) config('locks.ttl_seconds', 5);
        $this->waitSeconds = (int) config('locks.wait_seconds', 1);
    }

    public function execute(string $key, callable $callback): mixed
    {
        /** @var \Illuminate\Contracts\Cache\Repository&\Illuminate\Contracts\Cache\LockProvider $store */
        $store = $this->cache->store($this->store);

        return $store
            ->lock($this->prefix.':'.$key, $this->ttlSeconds)
            ->block($this->waitSeconds, $callback);
    }
}
