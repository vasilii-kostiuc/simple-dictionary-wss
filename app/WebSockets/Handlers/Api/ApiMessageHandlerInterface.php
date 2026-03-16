<?php

namespace App\WebSockets\Handlers\Api;

interface ApiMessageHandlerInterface
{
    public function handle(mixed $payload): void;
}
