<?php

namespace App\WebSockets\Handlers\Api;

class ApiMessageHandlerFactory
{
    /**
     * @param array<string, ApiMessageHandlerInterface> $handlers
     */
    public function __construct(
        private readonly array $handlers,
        private readonly ApiMessageHandlerInterface $unknownHandler,
    ) {
    }

    public function create(string $type): ApiMessageHandlerInterface
    {
        return $this->handlers[$type] ?? $this->unknownHandler;
    }
}
