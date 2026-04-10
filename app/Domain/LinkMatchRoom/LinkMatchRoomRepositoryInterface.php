<?php

namespace App\Domain\LinkMatchRoom;

interface LinkMatchRoomRepositoryInterface
{
    public function create(LinkMatchRoom $room): void;

    public function update(LinkMatchRoom $room): void;

    public function findByLinkMatchId(string $linkMatchId): ?LinkMatchRoom;

    public function deleteByLinkMatchId(string $linkMatchId): void;
}