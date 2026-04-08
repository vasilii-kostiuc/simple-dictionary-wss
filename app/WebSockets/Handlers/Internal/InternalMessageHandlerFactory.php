<?php

namespace App\WebSockets\Handlers\Internal;

class InternalMessageHandlerFactory
{
    /**
     * @param array<string, InternalMessageHandlerInterface> $handlers
     */
    public function __construct(
        private readonly array $handlers,
        private readonly InternalMessageHandlerInterface $unknownHandler,
    ) {
    }

    public function create(string $type): InternalMessageHandlerInterface
    {
        return $this->handlers[$type] ?? $this->unknownHandler;
    }
}
