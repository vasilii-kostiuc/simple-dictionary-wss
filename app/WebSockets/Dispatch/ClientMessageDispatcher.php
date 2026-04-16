<?php

namespace App\WebSockets\Dispatch;

use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Messages\ErrorMessage;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class ClientMessageDispatcher
{
    private string $nodeId;

    public function __construct(
        private readonly MessageHandlerFactory $messageHandlerFactory,
    ) {
        $this->nodeId = env('WSS_NODE_ID', gethostname());
    }

    public function dispatch(ConnectionInterface $conn, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), false);

        if ($payload === null) {
            Log::warning('[{node}] Invalid JSON received', ['node' => $this->nodeId, 'conn_id' => $conn->resourceId, 'raw' => $msg->getPayload()]);
            $conn->send(new ErrorMessage('invalid_json', $msg->getPayload()));

            return;
        }

        $type = $payload->type ?? '';
        Log::debug('[{node}] Message received', ['node' => $this->nodeId, 'conn_id' => $conn->resourceId, 'type' => $type]);

        $handler = $this->messageHandlerFactory->create($type, $payload);
        $handler->handle($conn, $msg);
    }
}
