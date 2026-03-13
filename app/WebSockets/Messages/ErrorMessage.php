<?php

namespace App\WebSockets\Messages;

class ErrorMessage extends WebSocketMessage
{
    public function __construct(string $error, mixed $clientPayload = null)
    {
        parent::__construct('error', [
            'error' => $error,
            'client_payload' => $clientPayload,
        ]);
    }
}
