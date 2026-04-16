<?php

namespace App\WebSockets\Handlers\Internal;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;

class RelayMessageHandler implements InternalMessageHandlerInterface
{
    public function __construct(
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(mixed $payload): void
    {
        $identifier = $payload['data']['identifier'] ?? null;
        $messageData = $payload['data']['message'] ?? [];

        if ($identifier === null || empty($messageData)) {
            return;
        }

        $wsMessage = new WebSocketMessage(
            $messageData['type'] ?? '',
            $messageData['data'] ?? [],
        );

        $this->sender->sendToIdentifier($identifier, $wsMessage);
    }
}
