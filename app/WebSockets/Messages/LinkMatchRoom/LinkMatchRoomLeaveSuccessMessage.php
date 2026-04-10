<?php

namespace App\WebSockets\Messages\LinkMatchRoom;

use App\WebSockets\Messages\WebSocketMessage;

class LinkMatchRoomLeaveSuccessMessage extends WebSocketMessage
{
    public function __construct()
    {
        parent::__construct('link_match_room_leave_success', []);
    }
}
