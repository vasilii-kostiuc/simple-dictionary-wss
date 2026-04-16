<?php

namespace App\Domain\LinkMatchRoom;

use App\Domain\LinkMatch\LinkMatch;

interface LinkMatchRoomRepositoryInterface
{
    public function getOrCreate(LinkMatch $linkMatch): LinkMatchRoom;

    public function update(LinkMatchRoom $room): void;

    public function findByLinkMatchId(string $linkMatchId): ?LinkMatchRoom;

    public function deleteByLinkMatchId(string $linkMatchId): void;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function executeInLock(string $roomId, callable $callback): mixed;
}
