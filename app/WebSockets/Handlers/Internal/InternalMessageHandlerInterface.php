<?php

namespace App\WebSockets\Handlers\Internal;

interface InternalMessageHandlerInterface
{
    public function handle(mixed $payload): void;
}
