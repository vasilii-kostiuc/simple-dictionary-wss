<?php

namespace App\WebSockets\ApiMessageHandlers;

interface ApiMessageHandlerInterface
{
    public function handle(string $channel, mixed $data): void;
}
