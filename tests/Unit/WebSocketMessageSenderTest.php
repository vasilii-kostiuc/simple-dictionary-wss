<?php

namespace Tests\Unit;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class WebSocketMessageSenderTest extends TestCase
{
    private function makeSender(ClientsStorageInterface $storage): WebSocketMessageSender
    {
        return new WebSocketMessageSender($storage);
    }

    private function makeMessage(): WebSocketMessage
    {
        return new WebSocketMessage('test_event', ['key' => 'value']);
    }

    public function test_sends_to_all_connections_of_user(): void
    {
        $conn1 = $this->createMock(ConnectionInterface::class);
        $conn2 = $this->createMock(ConnectionInterface::class);
        $message = $this->makeMessage();

        $conn1->expects($this->once())->method('send')->with($message);
        $conn2->expects($this->once())->method('send')->with($message);

        $storage = $this->createMock(ClientsStorageInterface::class);
        $storage->method('getConnectionsByIdentifier')->with('42')->willReturn([$conn1, $conn2]);

        $this->makeSender($storage)->sendToIdentifier('42', $message);
    }

    public function test_sends_nothing_when_user_has_no_connections(): void
    {
        $message = $this->makeMessage();

        $storage = $this->createMock(ClientsStorageInterface::class);
        $storage->method('getConnectionsByIdentifier')->with('99')->willReturn([]);

        // No exception — just silently does nothing
        $this->makeSender($storage)->sendToIdentifier('99', $message);
        $this->assertTrue(true);
    }
}
