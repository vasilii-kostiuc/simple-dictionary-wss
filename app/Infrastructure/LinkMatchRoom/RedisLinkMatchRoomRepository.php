<?php

namespace App\Infrastructure\LinkMatchRoom;

use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatchRoom\LinkMatchRoom;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\LinkMatchRoom\LinkMatchRoomStatus;
use App\Domain\Match\MatchParams;
use App\Domain\Shared\Identity\ClientIdentity;
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
            matchParams: ! empty($d['match_params']) ? MatchParams::fromArray($d['match_params']) : null,
        );
    }

    public function deleteByLinkMatchId(string $linkMatchId): void
    {
        Redis::del($this->key($linkMatchId));
    }

    public function executeInLock(string $roomId, callable $callback): mixed
    {
        $lockKey = 'lock:link_match_room:'.$roomId;
        $lockValue = bin2hex(random_bytes(16));
        $acquired = false;

        for ($i = 0; $i < 20; $i++) {
            if (Redis::set($lockKey, $lockValue, 'EX', 5, 'NX')) {
                $acquired = true;
                break;
            }
            usleep(50_000);
        }

        if (! $acquired) {
            throw new \RuntimeException("Failed to acquire lock for room: {$roomId}");
        }

        try {
            return $callback();
        } finally {
            Redis::eval(
                "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end",
                1,
                $lockKey,
                $lockValue,
            );
        }
    }

    private function key(string $linkMatchId): string
    {
        return self::PREFIX.$linkMatchId;
    }
}
