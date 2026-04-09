<?php

namespace Tests\Unit;

use App\WebSockets\Lifecycle\ConnectionLifecycleService;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('log', new class
        {
            public function info(...$args): void {}

            public function warning(...$args): void {}

            public function error(...$args): void {}
        });

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_on_close_removes_connection_and_unsubscribes_all_channels(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 42;

        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $subscriptions = $this->createMock(SubscriptionsStorageInterface::class);

        $clientRegistry->expects($this->once())->method('forget')->with($connection);
        $subscriptions->expects($this->once())->method('unsubscribeAll')->with($connection);

        (new ConnectionLifecycleService($clientRegistry, $subscriptions))->onClose($connection);
    }
}
