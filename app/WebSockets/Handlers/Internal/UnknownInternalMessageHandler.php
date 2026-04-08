<?php

namespace App\WebSockets\Handlers\Internal;

use Illuminate\Support\Facades\Log;

class UnknownInternalMessageHandler implements InternalMessageHandlerInterface
{
    public function handle(mixed $payload): void
    {
        Log::warning('Unknown internal message type', ['payload' => $payload]);
    }
}
