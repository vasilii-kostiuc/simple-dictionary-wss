<?php

namespace App\Domain\LinkMatchRoom;

use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatchRoom\Events\ParticipantJoinedEvent;
use App\Domain\LinkMatchRoom\Events\ParticipantLeftEvent;
use App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent;
use App\Domain\Shared\AggregateRoot;
use App\Domain\Shared\Identity\ClientIdentity;
use Carbon\Carbon;

class LinkMatchRoom extends AggregateRoot
{
    /** @var array<string, ClientIdentity> */
    private array $participants = [];

    private array $matchParams = [];

    private LinkMatchRoomStatus $status = LinkMatchRoomStatus::WaitingForPlayers;

    private ?int $matchId = null;

    private readonly Carbon $createdAt;

    private function __construct(
        public readonly string $linkMatchId,
        public readonly int $participantsLimit,
        ?Carbon $createdAt = null,
    ) {
        $this->createdAt = $createdAt ?? Carbon::now();
    }

    public function joinParticipant(ClientIdentity $clientIdentity): void
    {
        if ($this->status !== LinkMatchRoomStatus::WaitingForPlayers) {
            throw new \DomainException('Cannot join room in current status');
        }

        if ($this->isFull()) {
            throw new \DomainException('Room is full');
        }

        $identifier = $clientIdentity->getIdentifier();

        if (! isset($this->participants[$identifier])) {
            $this->participants[$identifier] = $clientIdentity;
        }

        $this->recordEvent(new ParticipantJoinedEvent($this->linkMatchId, $identifier, $this->getParticipants()));

        if ($this->isFull()) {
            $this->status = LinkMatchRoomStatus::Full;
            $this->recordEvent(new RoomBecameFullEvent($this->linkMatchId, array_values($this->participants), $this->matchParams));
        }
    }

    public function leaveParticipant(ClientIdentity $clientIdentity): void
    {
        if ($this->status === LinkMatchRoomStatus::MatchCreating || $this->status === LinkMatchRoomStatus::MatchCreated) {
            throw new \DomainException('Cannot leave room in current status');
        }

        $identifier = $clientIdentity->getIdentifier();
        unset($this->participants[$identifier]);

        $this->recordEvent(new ParticipantLeftEvent($this->linkMatchId, $identifier, $this->getParticipants()));

        if ($this->status === LinkMatchRoomStatus::Full) {
            $this->status = LinkMatchRoomStatus::WaitingForPlayers;
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->participants);
    }

    public function isFull(): bool
    {
        return count($this->participants) >= $this->participantsLimit;
    }

    public static function create(LinkMatch $linkMatch): self
    {
        $room = new self($linkMatch->id, $linkMatch->participantsLimit);
        $room->matchParams = $linkMatch->payload;

        return $room;
    }

    public function setMatchCreating(): void
    {
        if ($this->status !== LinkMatchRoomStatus::Full) {
            throw new \DomainException('Can not start match creation');
        }

        $this->status = LinkMatchRoomStatus::MatchCreating;
    }

    public function setMatchCreated(int $matchId): void
    {
        if ($this->status != LinkMatchRoomStatus::MatchCreating) {
            throw new \DomainException('Can not set match as created');
        }

        $this->status = LinkMatchRoomStatus::MatchCreated;
        $this->matchId = $matchId;
    }

    public function getId(): string
    {
        return $this->linkMatchId;
    }

    public function getParticipants(): array
    {
        return array_values(array_map(fn (ClientIdentity $identity) => $identity->getIdentifier(), $this->participants));
    }

    public function getParticipantIdentities(): array
    {
        return array_values($this->participants);
    }

    public function getParticipantsLimit(): int
    {
        return $this->participantsLimit;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getStatus(): LinkMatchRoomStatus
    {
        return $this->status;
    }

    public function getMatchId(): ?int
    {
        return $this->matchId;
    }

    public function toArray(): array
    {
        return [
            'link_match_id' => $this->linkMatchId,
            'participants_limit' => $this->participantsLimit,
            'participants' => array_values(array_map(
                fn (ClientIdentity $identity) => [
                    'id' => $identity->id,
                    'name' => $identity->name,
                    'email' => $identity->email,
                    'avatar' => $identity->avatar,
                    'guest_id' => $identity->guestId,
                ],
                $this->participants,
            )),
            'match_params' => $this->matchParams,
            'status' => $this->status->value,
            'match_id' => $this->matchId,
            'created_at' => $this->createdAt->toIso8601String(),
        ];
    }

    /**
     * @param  ClientIdentity[]  $participants
     */
    public static function reconstitute(
        string $linkMatchId,
        int $participantsLimit,
        array $participants,
        LinkMatchRoomStatus $status,
        ?int $matchId,
        Carbon $createdAt,
        array $matchParams = [],
    ): self {
        $room = new self($linkMatchId, $participantsLimit, $createdAt);

        foreach ($participants as $identity) {
            $room->participants[$identity->getIdentifier()] = $identity;
        }

        $room->matchParams = $matchParams;
        $room->status = $status;
        $room->matchId = $matchId;

        return $room;
    }
}
