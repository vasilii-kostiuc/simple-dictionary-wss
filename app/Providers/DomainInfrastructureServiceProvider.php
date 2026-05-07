<?php

namespace App\Providers;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\LockManagerInterface;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;
use App\Infrastructure\Identity\RandomGuestIdentityFactory;
use App\Infrastructure\Identity\SimpleDictionaryApiUserIdentityResolver;
use App\Infrastructure\LinkMatchRoom\RedisLinkMatchRoomRepository;
use App\Infrastructure\MatchMaking\RedisMatchMakingQueue;
use App\Infrastructure\Shared\CacheLockManager;
use App\Infrastructure\Shared\LaravelEventDispatcher;
use App\Infrastructure\Shared\MongoTimerStorage;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class DomainInfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);

        $this->app->singleton(MatchMakingQueueInterface::class, function () {
            return new RedisMatchMakingQueue;
        });

        $this->app->singleton(GuestIdentityFactoryInterface::class, function () {
            return new RandomGuestIdentityFactory;
        });

        $this->app->singleton(UserIdentityResolverInterface::class, function (Application $app) {
            return new SimpleDictionaryApiUserIdentityResolver(
                $app->make(\App\Application\Contracts\SimpleDictionaryApiClientInterface::class),
            );
        });

        $this->app->singleton(LinkMatchRoomRepositoryInterface::class, function () {
            return new RedisLinkMatchRoomRepository;
        });

        $this->app->singleton(LockManagerInterface::class, function (Application $app) {
            return new CacheLockManager($app->make(CacheFactory::class));
        });

        $this->app->singleton(TimerStorageInterface::class, function () {
            return new MongoTimerStorage;
        });
    }
}
