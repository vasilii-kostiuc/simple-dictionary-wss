<?php

namespace App\WebSockets\Handlers\Client;

use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class AuthorizedMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly MessageHandlerInterface $inner,
        private readonly ClientRegistryInterface $clientRegistry,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $identifier = $this->clientRegistry->getIdentifierByConnection($from);

        if ($identifier === null) {
            $payload = json_decode($msg->getPayload(), true) ?? [];
            $from->send(new ErrorMessage('not_authorized', $payload));
            return;
        }

        $this->inner->handle($from, $msg);
    }
}
