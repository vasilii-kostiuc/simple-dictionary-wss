<?php

namespace Tests\Unit;

use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatchRoom\LinkMatchRoom;
use App\Domain\LinkMatchRoom\LinkMatchRoomStatus;
use App\Domain\LinkMatch\LinkMatchStatus;
use App\Domain\Shared\Identity\ClientIdentity;
use PHPUnit\Framework\TestCase;

class LinkMatchRoomTest extends TestCase
{
    // --- Helpers ---

    private function makeLinkMatch(string $id = 'lm-1', int $participantsLimit = 2): LinkMatch
    {
        return new LinkMatch(
            id: $id,
            token: 'test-token',
            participantsLimit: $participantsLimit,
            status: LinkMatchStatus::Pending,
        );
    }

    private function makeClient(string $identifier): ClientIdentity
    {
        return new ClientIdentity(
            id: is_numeric($identifier) ? (int) $identifier : null,
            name: 'User '.$identifier,
            email: 'user-'.$identifier.'@test.com',
            avatar: null,
            guestId: is_numeric($identifier) ? null : $identifier,
        );
    }

    private function makeFullRoom(int $limit = 2): LinkMatchRoom
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: $limit));

        for ($i = 1; $i <= $limit; $i++) {
            $room->joinParticipant($this->makeClient((string) $i));
        }

        return $room;
    }

    // --- create() ---

    public function test_create_sets_correct_initial_state(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch('lm-42', 3));

        $this->assertSame('lm-42', $room->getId());
        $this->assertSame(3, $room->getParticipantsLimit());
        $this->assertSame(LinkMatchRoomStatus::WaitingForPlayers, $room->getStatus());
        $this->assertEmpty($room->getParticipants());
        $this->assertNull($room->getMatchId());
        $this->assertTrue($room->isEmpty());
        $this->assertFalse($room->isFull());
    }

    // --- joinParticipant() ---

    public function test_join_adds_participant(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 2));

        $room->joinParticipant($this->makeClient('1'));

        $this->assertContains('1', $room->getParticipants());
        $this->assertCount(1, $room->getParticipants());
        $this->assertFalse($room->isEmpty());
        $this->assertFalse($room->isFull());
        $this->assertSame(LinkMatchRoomStatus::WaitingForPlayers, $room->getStatus());
    }

    public function test_join_is_idempotent_for_same_participant(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 3));
        $client = $this->makeClient('1');

        $room->joinParticipant($client);
        $room->joinParticipant($client);

        $this->assertCount(1, $room->getParticipants());
    }

    public function test_join_sets_status_full_when_limit_reached(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 2));

        $room->joinParticipant($this->makeClient('1'));
        $room->joinParticipant($this->makeClient('2'));

        $this->assertSame(LinkMatchRoomStatus::Full, $room->getStatus());
        $this->assertTrue($room->isFull());
    }

    public function test_join_throws_when_room_is_full(): void
    {
        $room = $this->makeFullRoom(2);

        $this->expectException(\DomainException::class);
        $room->joinParticipant($this->makeClient('3'));
    }

    public function test_join_throws_when_status_is_not_waiting_for_players(): void
    {
        $room = $this->makeFullRoom(2);
        $room->setMatchCreating();

        $this->expectException(\DomainException::class);
        $room->joinParticipant($this->makeClient('3'));
    }

    // --- leaveParticipant() ---

    public function test_leave_removes_participant(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 3));
        $client = $this->makeClient('1');
        $room->joinParticipant($client);
        $room->joinParticipant($this->makeClient('2'));

        $room->leaveParticipant($client);

        $this->assertNotContains('1', $room->getParticipants());
        $this->assertCount(1, $room->getParticipants());
    }

    public function test_leave_from_full_reverts_status_to_waiting(): void
    {
        $room = $this->makeFullRoom(2);
        $this->assertSame(LinkMatchRoomStatus::Full, $room->getStatus());

        $room->leaveParticipant($this->makeClient('1'));

        $this->assertSame(LinkMatchRoomStatus::WaitingForPlayers, $room->getStatus());
    }

    public function test_leave_throws_when_match_creating(): void
    {
        $room = $this->makeFullRoom(2);
        $room->setMatchCreating();

        $this->expectException(\DomainException::class);
        $room->leaveParticipant($this->makeClient('1'));
    }

    public function test_leave_throws_when_match_created(): void
    {
        $room = $this->makeFullRoom(2);
        $room->setMatchCreating();
        $room->setMatchCreated(99);

        $this->expectException(\DomainException::class);
        $room->leaveParticipant($this->makeClient('1'));
    }

    // --- setMatchCreating() ---

    public function test_set_match_creating_transitions_status(): void
    {
        $room = $this->makeFullRoom(2);

        $room->setMatchCreating();

        $this->assertSame(LinkMatchRoomStatus::MatchCreating, $room->getStatus());
    }

    public function test_set_match_creating_throws_when_not_full(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 2));
        $room->joinParticipant($this->makeClient('1'));

        $this->expectException(\DomainException::class);
        $room->setMatchCreating();
    }

    // --- setMatchCreated() ---

    public function test_set_match_created_sets_match_id_and_status(): void
    {
        $room = $this->makeFullRoom(2);
        $room->setMatchCreating();

        $room->setMatchCreated(42);

        $this->assertSame(42, $room->getMatchId());
        $this->assertSame(LinkMatchRoomStatus::MatchCreated, $room->getStatus());
    }

    public function test_set_match_created_throws_when_not_match_creating(): void
    {
        $room = $this->makeFullRoom(2);

        $this->expectException(\DomainException::class);
        $room->setMatchCreated(1);
    }

    // --- guest identity ---

    public function test_guest_participant_uses_guest_id_as_identifier(): void
    {
        $room = LinkMatchRoom::create($this->makeLinkMatch(participantsLimit: 2));
        $guest = $this->makeClient('guest-abc');

        $room->joinParticipant($guest);

        $this->assertContains('guest-abc', $room->getParticipants());
    }
}
