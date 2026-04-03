<?php

namespace App\WebSockets\Dispatch;

use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Messages\ErrorMessage;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class ClientMessageDispatcher
{
    public function __construct(
        private readonly MessageHandlerFactory $messageHandlerFactory,
    ) {
    }

    public function dispatch(ConnectionInterface $conn, MessageInterface $msg): void
    {
        Log::info(__METHOD__.' '.$msg);
        Log::info(get_class($msg));

        $payload = json_decode($msg->getPayload(), false);

        if ($payload === null) {
            Log::warning('Invalid JSON received: '.$msg->getPayload());
            $conn->send(new ErrorMessage('invalid_json', $msg->getPayload()));

            return;
        }

        $handler = $this->messageHandlerFactory->create($payload->type ?? '', $payload);
        $handler->handle($conn, $msg);
    }
}
