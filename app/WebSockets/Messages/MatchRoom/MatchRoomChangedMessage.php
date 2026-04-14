<?php

namespace App\WebSockets\Messages\MatchRoom;

use App\WebSockets\Messages\WebSocketMessage;

class MatchRoomChangedMessage extends WebSocketMessage
{
    public function __construct(string $roomId, array $payload = [])
    {
        parent::__construct(
            'match_room.changed',
            array_merge(['room_id' => $roomId], $payload),
        );
    }
}
