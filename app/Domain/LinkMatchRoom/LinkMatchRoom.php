<?php

namespace App\Domain\LinkMatchRoom;

use App\Domain\LinkMatch\LinkMatch;
use App\Domain\Shared\Identity\ClientIdentity;
use Carbon\Carbon;

class LinkMatchRoom
{
    private array $participants = [];
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

        if (! in_array($identifier, $this->participants)) {
            $this->participants[] = $identifier;
        }

        if ($this->isFull()) {
            $this->status = LinkMatchRoomStatus::Full;
        }
    }

    public function leaveParticipant(ClientIdentity $clientIdentity): void
    {
        if ($this->status === LinkMatchRoomStatus::MatchCreating || $this->status === LinkMatchRoomStatus::MatchCreated) {
            throw new \DomainException('Cannot leave room in current status');
        }

        $identifier = $clientIdentity->getIdentifier();
        $this->participants = array_filter($this->participants, fn ($id) => $id !== $identifier);

        if ($this->status == LinkMatchRoomStatus::Full) {
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
        return new self($linkMatch->id, $linkMatch->participantsLimit);
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
        return $this->participants;
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
            'participants' => $this->participants,
            'status' => $this->status->value,
            'match_id' => $this->matchId,
            'created_at' => $this->createdAt->toIso8601String(),
        ];
    }

    public static function reconstitute(string $linkMatchId, int $participantsLimit, array $participants, LinkMatchRoomStatus $status, ?int $matchId, Carbon $createdAt): self
    {
        $room = new self($linkMatchId, $participantsLimit, $createdAt);
        $room->participants = $participants;
        $room->status = $status;
        $room->matchId = $matchId;

        return $room;
    }

}