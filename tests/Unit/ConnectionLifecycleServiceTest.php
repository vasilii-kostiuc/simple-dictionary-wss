<?php

namespace Tests\Unit;

use App\Application\LinkMatchRoom\Actions\DisconnectFromLinkMatchRoomAction;
use App\Application\MatchMaking\Actions\LeaveMatchMakingAction;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentity;
use App\WebSockets\Lifecycle\ConnectionLifecycleService;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class ConnectionLifecycleServiceTest extends TestCase
{
    private ClientRegistryInterface $clientRegistry;

    private SubscriptionsStorageInterface $subscriptions;

    private DisconnectFromLinkMatchRoomAction $disconnectFromRoomAction;

    private LeaveMatchMakingAction $leaveMatchMakingAction;

    private MatchMakingQueueInterface $matchMakingQueue;

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

        $this->clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $this->subscriptions = $this->createMock(SubscriptionsStorageInterface::class);
        $this->disconnectFromRoomAction = $this->createMock(DisconnectFromLinkMatchRoomAction::class);
        $this->leaveMatchMakingAction = $this->createMock(LeaveMatchMakingAction::class);
        $this->matchMakingQueue = $this->createMock(MatchMakingQueueInterface::class);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    private function makeService(): ConnectionLifecycleService
    {
        return new ConnectionLifecycleService(
            $this->clientRegistry,
            $this->subscriptions,
            $this->disconnectFromRoomAction,
            $this->leaveMatchMakingAction,
            $this->matchMakingQueue,
        );
    }

    public function test_on_close_when_no_identity_only_forgets_and_unsubscribes(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 42;

        $this->clientRegistry->method('getIdentity')->willReturn(null);
        $this->subscriptions->method('getChannelsByConnection')->willReturn([]);

        $this->clientRegistry->expects($this->once())->method('forget')->with($connection);
        $this->subscriptions->expects($this->once())->method('unsubscribeAll')->with($connection);
        $this->disconnectFromRoomAction->expects($this->never())->method('execute');
        $this->leaveMatchMakingAction->expects($this->never())->method('execute');

        $this->makeService()->onClose($connection);
    }

    public function test_on_close_disconnects_from_link_match_room(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 42;

        $identity = $this->createMock(ClientIdentity::class);
        $identity->method('getIdentifier')->willReturn('user:1');

        $this->clientRegistry->method('getIdentity')->willReturn($identity);
        $this->subscriptions->method('getChannelsByConnection')
            ->willReturn(['link_match_room.room-abc']);
        $this->matchMakingQueue->method('isUserInQueue')->willReturn(false);

        $this->disconnectFromRoomAction->expects($this->once())
            ->method('execute')
            ->with($identity, 'room-abc');
        $this->leaveMatchMakingAction->expects($this->never())->method('execute');

        $this->makeService()->onClose($connection);
    }

    public function test_on_close_leaves_matchmaking_queue(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 42;

        $identity = $this->createMock(ClientIdentity::class);
        $identity->method('getIdentifier')->willReturn('user:1');

        $this->clientRegistry->method('getIdentity')->willReturn($identity);
        $this->subscriptions->method('getChannelsByConnection')->willReturn([]);
        $this->matchMakingQueue->method('isUserInQueue')->with('user:1')->willReturn(true);

        $this->leaveMatchMakingAction->expects($this->once())
            ->method('execute')
            ->with('user:1');

        $this->makeService()->onClose($connection);
    }

    public function test_on_close_handles_both_cleanup_types(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 42;

        $identity = $this->createMock(ClientIdentity::class);
        $identity->method('getIdentifier')->willReturn('user:1');

        $this->clientRegistry->method('getIdentity')->willReturn($identity);
        $this->subscriptions->method('getChannelsByConnection')
            ->willReturn(['link_match_room.room-xyz', 'other_channel']);
        $this->matchMakingQueue->method('isUserInQueue')->willReturn(true);

        $this->disconnectFromRoomAction->expects($this->once())
            ->method('execute')->with($identity, 'room-xyz');
        $this->leaveMatchMakingAction->expects($this->once())
            ->method('execute')->with('user:1');

        $this->makeService()->onClose($connection);
    }
}
