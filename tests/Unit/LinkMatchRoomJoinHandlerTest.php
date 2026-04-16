<?php

namespace Tests\Unit;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\LockManagerInterface;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\LinkMatchRoom\Actions\JoinLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatch\LinkMatchStatus;
use App\Domain\LinkMatchRoom\Events\ParticipantJoinedEvent;
use App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent;
use App\Domain\LinkMatchRoom\LinkMatchRoom;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\Shared\Identity\ClientIdentity;
use PHPUnit\Framework\TestCase;

class LinkMatchRoomJoinHandlerTest extends TestCase
{
    private SimpleDictionaryApiClientInterface $apiClient;

    private LinkMatchRoomRepositoryInterface $roomRepository;

    private EventDispatcherInterface $eventDispatcher;

    private LockManagerInterface $lockManager;

    private ClientIdentity $identity;

    private LinkMatch $linkMatch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this->createMock(SimpleDictionaryApiClientInterface::class);
        $this->roomRepository = $this->createMock(LinkMatchRoomRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->identity = new ClientIdentity(1, 'Alice', 'alice@example.com', null);
        $this->linkMatch = new LinkMatch('lm-1', 'tok_abc', 2, LinkMatchStatus::Pending);

        $this->lockManager = $this->createMock(LockManagerInterface::class);
        $this->lockManager->method('execute')
            ->willReturnCallback(fn (string $key, callable $callback) => $callback());
    }

    private function action(): JoinLinkMatchRoomAction
    {
        return new JoinLinkMatchRoomAction($this->apiClient, $this->roomRepository, $this->eventDispatcher, $this->lockManager);
    }

    private function makeRoom(): LinkMatchRoom
    {
        return LinkMatchRoom::create($this->linkMatch);
    }

    public function test_creates_new_room_and_joins_when_room_does_not_exist(): void
    {
        $room = $this->makeRoom();

        $this->apiClient->method('getLinkMatch')->with('tok_abc')->willReturn($this->linkMatch);

        $this->roomRepository->expects($this->once())
            ->method('getOrCreate')
            ->with($this->linkMatch)
            ->willReturn($room);

        $this->roomRepository->expects($this->once())
            ->method('update')
            ->with($room);

        $result = $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);

        $this->assertSame($room, $result['room']);
        $this->assertContains($this->identity->getIdentifier(), $room->getParticipants());
    }

    public function test_dispatches_participant_joined_event(): void
    {
        $room = $this->makeRoom();

        $this->apiClient->method('getLinkMatch')->willReturn($this->linkMatch);
        $this->roomRepository->method('getOrCreate')->willReturn($room);
        $this->roomRepository->method('update');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ParticipantJoinedEvent::class));

        $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);
    }

    public function test_dispatches_room_became_full_event_when_room_fills_up(): void
    {
        $room = $this->makeRoom();
        $room->joinParticipant(new ClientIdentity(2, 'Bob', 'bob@example.com', null));
        $room->pullEvents(); // сбрасываем Bob's join event

        $this->apiClient->method('getLinkMatch')->willReturn($this->linkMatch);
        $this->roomRepository->method('getOrCreate')->willReturn($room);
        $this->roomRepository->method('update');

        $dispatchedEvents = [];
        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            });

        $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);

        $eventTypes = array_map(fn ($e) => get_class($e), $dispatchedEvents);
        $this->assertContains(ParticipantJoinedEvent::class, $eventTypes);
        $this->assertContains(RoomBecameFullEvent::class, $eventTypes);
    }

    public function test_joins_existing_room_via_get_or_create(): void
    {
        $room = $this->makeRoom();
        $other = new ClientIdentity(2, 'Bob', 'bob@example.com', null);
        $room->joinParticipant($other);

        $this->apiClient->method('getLinkMatch')->willReturn($this->linkMatch);
        $this->roomRepository->method('getOrCreate')->willReturn($room);
        $this->roomRepository->expects($this->once())->method('update');

        $result = $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);

        $this->assertContains($this->identity->getIdentifier(), $result['room']->getParticipants());
        $this->assertContains($other->getIdentifier(), $result['room']->getParticipants());
    }

    public function test_throws_when_link_token_is_missing(): void
    {
        try {
            $this->action()->execute($this->identity, []);
            $this->fail('Expected LinkMatchRoomException');
        } catch (LinkMatchRoomException $e) {
            $this->assertSame('link_not_found', $e->getErrorCode());
        }
    }

    public function test_throws_when_link_not_found_in_api(): void
    {
        $this->apiClient->method('getLinkMatch')->willReturn(null);

        try {
            $this->action()->execute($this->identity, ['link_token' => 'bad_token']);
            $this->fail('Expected LinkMatchRoomException');
        } catch (LinkMatchRoomException $e) {
            $this->assertSame('link_not_found', $e->getErrorCode());
        }
    }

    public function test_throws_when_link_is_expired(): void
    {
        $expired = new LinkMatch('lm-1', 'tok_abc', 2, LinkMatchStatus::Expired);
        $this->apiClient->method('getLinkMatch')->willReturn($expired);

        try {
            $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);
            $this->fail('Expected LinkMatchRoomException');
        } catch (LinkMatchRoomException $e) {
            $this->assertSame('link_not_found', $e->getErrorCode());
        }
    }

    public function test_throws_when_room_is_full(): void
    {
        $room = $this->makeRoom();
        $room->joinParticipant(new ClientIdentity(2, 'Bob', 'bob@example.com', null));
        $room->joinParticipant(new ClientIdentity(3, 'Carol', 'carol@example.com', null));

        $this->apiClient->method('getLinkMatch')->willReturn($this->linkMatch);
        $this->roomRepository->method('getOrCreate')->willReturn($room);

        try {
            $this->action()->execute($this->identity, ['link_token' => 'tok_abc']);
            $this->fail('Expected LinkMatchRoomException');
        } catch (LinkMatchRoomException $e) {
            $this->assertSame('link_match_room_full', $e->getErrorCode());
        }
    }
}
