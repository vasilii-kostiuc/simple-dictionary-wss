<?php

namespace Tests\Unit;

use App\Application\LinkMatchRoom\Listeners\CreateMatchOnRoomFullListener;
use App\Application\Match\Actions\CreateMatchAction;
use App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent;
use App\Domain\Match\MatchParticipant;
use App\Domain\Shared\Identity\ClientIdentity;
use PHPUnit\Framework\TestCase;

class CreateMatchOnRoomFullListenerTest extends TestCase
{
    private CreateMatchAction $createMatchAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMatchAction = $this->createMock(CreateMatchAction::class);
    }

    private function listener(): CreateMatchOnRoomFullListener
    {
        return new CreateMatchOnRoomFullListener($this->createMatchAction);
    }

    private function makeUser(int $id, string $name = 'User', ?string $avatar = null): ClientIdentity
    {
        return new ClientIdentity(
            id: $id,
            name: $name,
            email: $name.'@example.com',
            avatar: $avatar,
        );
    }

    private function makeGuest(string $guestId, string $name = 'Guest', ?string $avatar = null): ClientIdentity
    {
        return new ClientIdentity(
            id: null,
            name: $name,
            email: '',
            avatar: $avatar,
            guestId: $guestId,
        );
    }

    // --- handle() ---

    public function test_calls_create_match_action_with_mapped_participants(): void
    {
        $user = $this->makeUser(1, 'Alice');
        $guest = $this->makeGuest('guest-abc', 'Bob', 'http://avatar.test/bob.png');

        $event = new RoomBecameFullEvent(
            roomId: 'lm-1',
            participants: [$user, $guest],
            matchParams: ['wordPack' => 'basic'],
        );

        $capturedParticipants = null;
        $capturedParams = null;

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $participants, array $params) use (&$capturedParticipants, &$capturedParams) {
                $capturedParticipants = $participants;
                $capturedParams = $params;

                return [];
            });

        $this->listener()->handle($event);

        $this->assertCount(2, $capturedParticipants);

        $this->assertInstanceOf(MatchParticipant::class, $capturedParticipants[0]);
        $this->assertSame('1', $capturedParticipants[0]->id);
        $this->assertSame('user', $capturedParticipants[0]->type);
        $this->assertNull($capturedParticipants[0]->name);
        $this->assertNull($capturedParticipants[0]->avatar);

        $this->assertInstanceOf(MatchParticipant::class, $capturedParticipants[1]);
        $this->assertSame('guest-abc', $capturedParticipants[1]->id);
        $this->assertSame('guest', $capturedParticipants[1]->type);
        $this->assertSame('Bob', $capturedParticipants[1]->name);
        $this->assertSame('http://avatar.test/bob.png', $capturedParticipants[1]->avatar);

        $this->assertSame(['wordPack' => 'basic'], $capturedParams);
    }

    public function test_passes_match_params_from_event(): void
    {
        $event = new RoomBecameFullEvent(
            roomId: 'lm-2',
            participants: [$this->makeUser(10), $this->makeUser(20)],
            matchParams: ['difficulty' => 'hard', 'rounds' => 5],
        );

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->with($this->anything(), ['difficulty' => 'hard', 'rounds' => 5])
            ->willReturn([]);

        $this->listener()->handle($event);
    }

    public function test_passes_empty_match_params_when_not_set(): void
    {
        $event = new RoomBecameFullEvent(
            roomId: 'lm-3',
            participants: [$this->makeUser(1), $this->makeUser(2)],
        );

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->with($this->anything(), [])
            ->willReturn([]);

        $this->listener()->handle($event);
    }

    public function test_maps_all_participants_including_guests_correctly(): void
    {
        $participants = [
            $this->makeUser(1, 'Alice'),
            $this->makeUser(2, 'Bob'),
            $this->makeGuest('g-1', 'Charlie', 'http://cdn.test/charlie.jpg'),
        ];

        $event = new RoomBecameFullEvent(
            roomId: 'lm-4',
            participants: $participants,
            matchParams: [],
        );

        $capturedParticipants = null;

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $p) use (&$capturedParticipants) {
                $capturedParticipants = $p;

                return [];
            });

        $this->listener()->handle($event);

        $this->assertCount(3, $capturedParticipants);

        $this->assertSame('user', $capturedParticipants[0]->type);
        $this->assertNull($capturedParticipants[0]->name);

        $this->assertSame('user', $capturedParticipants[1]->type);
        $this->assertNull($capturedParticipants[1]->name);

        $this->assertSame('guest', $capturedParticipants[2]->type);
        $this->assertSame('Charlie', $capturedParticipants[2]->name);
        $this->assertSame('http://cdn.test/charlie.jpg', $capturedParticipants[2]->avatar);
    }

    public function test_user_participant_has_no_name_or_avatar(): void
    {
        $user = $this->makeUser(99, 'John', 'http://cdn.test/john.jpg');

        $event = new RoomBecameFullEvent(
            roomId: 'lm-5',
            participants: [$user, $this->makeUser(100)],
            matchParams: [],
        );

        $capturedParticipants = null;

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $p) use (&$capturedParticipants) {
                $capturedParticipants = $p;

                return [];
            });

        $this->listener()->handle($event);

        // Even if ClientIdentity has avatar, MatchParticipant for user type should have no name/avatar
        $this->assertNull($capturedParticipants[0]->name);
        $this->assertNull($capturedParticipants[0]->avatar);
    }

    public function test_guest_without_avatar_has_null_avatar(): void
    {
        $guest = $this->makeGuest('g-no-avatar', 'NoAvatar');

        $event = new RoomBecameFullEvent(
            roomId: 'lm-6',
            participants: [$this->makeUser(1), $guest],
            matchParams: [],
        );

        $capturedParticipants = null;

        $this->createMatchAction
            ->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $p) use (&$capturedParticipants) {
                $capturedParticipants = $p;

                return [];
            });

        $this->listener()->handle($event);

        $this->assertNull($capturedParticipants[1]->avatar);
        $this->assertSame('NoAvatar', $capturedParticipants[1]->name);
    }
}
