<?php

namespace App\Infrastructure\LinkMatchRoom;

use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatchRoom\LinkMatchRoom;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\LinkMatchRoom\LinkMatchRoomStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class RedisLinkMatchRoomRepository implements LinkMatchRoomRepositoryInterface
{
    private const PREFIX = 'link_match_room:';

    private const TTL = 86400; // 24 hours

    public function getOrCreate(LinkMatch $linkMatch): LinkMatchRoom
    {
        $existing = $this->findByLinkMatchId($linkMatch->id);

        if ($existing !== null) {
            return $existing;
        }

        $room = LinkMatchRoom::create($linkMatch);
        Redis::setex($this->key($room->getId()), self::TTL, json_encode($room->toArray()));

        return $room;
    }

    public function update(LinkMatchRoom $room): void
    {
        Redis::setex(
            $this->key($room->getId()),
            self::TTL,
            json_encode($room->toArray()),
        );
    }

    public function findByLinkMatchId(string $linkMatchId): ?LinkMatchRoom
    {
        $data = Redis::get($this->key($linkMatchId));

        if ($data === null) {
            return null;
        }

        $d = json_decode($data, true);

        $participants = array_map(
            fn (array $p) => new ClientIdentity(
                id: $p['id'],
                name: $p['name'],
                email: $p['email'],
                avatar: $p['avatar'],
                guestId: $p['guest_id'] ?? null,
            ),
            $d['participants'],
        );

        return LinkMatchRoom::reconstitute(
            linkMatchId: $d['link_match_id'],
            participantsLimit: $d['participants_limit'],
            participants: $participants,
            status: LinkMatchRoomStatus::from($d['status']),
            matchId: $d['match_id'],
            createdAt: Carbon::parse($d['created_at']),
            matchParams: $d['match_params'] ?? [],
        );
    }

    public function deleteByLinkMatchId(string $linkMatchId): void
    {
        Redis::del($this->key($linkMatchId));
    }

    private function key(string $linkMatchId): string
    {
        return self::PREFIX.$linkMatchId;
    }
}
