<?php

namespace Tests\Unit;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class WebSocketMessageSenderTest extends TestCase
{
    private function makeSender(ClientRegistryInterface $clientRegistry): WebSocketMessageSender
    {
        $messageBroker = $this->createMock(MessageBrokerInterface::class);

        return new WebSocketMessageSender($clientRegistry, $messageBroker);
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

        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $clientRegistry->method('getConnectionsByIdentifier')->with('42')->willReturn([$conn1, $conn2]);

        $this->makeSender($clientRegistry)->sendToIdentifier('42', $message);
    }

    public function test_sends_nothing_when_user_has_no_connections(): void
    {
        $message = $this->makeMessage();

        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $clientRegistry->method('getConnectionsByIdentifier')->with('99')->willReturn([]);

        // No exception — just silently does nothing
        $this->makeSender($clientRegistry)->sendToIdentifier('99', $message);
        $this->assertTrue(true);
    }
}
