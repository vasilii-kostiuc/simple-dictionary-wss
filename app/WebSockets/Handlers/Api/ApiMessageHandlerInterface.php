<?php

namespace App\WebSockets\Handlers\Api;

interface ApiMessageHandlerInterface
{
    public function handle(string $channel, mixed $data): void;
}
