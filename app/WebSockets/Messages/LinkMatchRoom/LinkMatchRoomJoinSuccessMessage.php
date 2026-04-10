<?php

namespace App\WebSockets\Messages\LinkMatchRoom;

use App\WebSockets\Messages\WebSocketMessage;

class LinkMatchRoomJoinSuccessMessage extends WebSocketMessage
{
    public function __construct(array $participants)
    {
        parent::__construct(
            'link_match_room_join_success',
            [
                'participants' => $participants,
            ]
        );
    }
}
