<?php

namespace App\WebSockets\Handlers\Internal;

interface InternalMessageHandlerInterface
{
    public function handle(string $channel, mixed $payload): void;
}
