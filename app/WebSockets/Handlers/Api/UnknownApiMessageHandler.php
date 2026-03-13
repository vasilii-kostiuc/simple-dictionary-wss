<?php

namespace App\WebSockets\Handlers\Api;

use Illuminate\Support\Facades\Log;

class UnknownApiMessageHandler implements ApiMessageHandlerInterface
{
    public function handle(string $channel, mixed $payload): void
    {
        Log::warning('Unknown API message type', [
            'channel' => $channel,
            'payload' => $payload
        ]);
    }
}
