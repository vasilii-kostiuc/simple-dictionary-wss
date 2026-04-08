<?php

namespace App\WebSockets\Handlers\Client;

use App\WebSockets\Enums\ClientRequestType;

class MessageHandlerFactory
{
    /**
     * @param array<string, MessageHandlerInterface> $handlers
     * @param \Closure(string $channel): MessageHandlerInterface $subscribeResolver
     */
    public function __construct(
        private readonly array $handlers,
        private readonly \Closure $subscribeResolver,
        private readonly MessageHandlerInterface $unknownHandler,
    ) {
    }

    public function create(string $type, object $payload): MessageHandlerInterface
    {
        $requestType = ClientRequestType::tryFrom($type);

        if ($requestType === null) {
            return $this->unknownHandler;
        }

        if ($requestType === ClientRequestType::Subscribe) {
            $channel = $payload->data?->channel ?? '';
            return ($this->subscribeResolver)($channel);
        }

        return $this->handlers[$requestType->value] ?? $this->unknownHandler;
    }
}
