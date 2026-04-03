<?php

namespace Tests\Unit;

use App\WebSockets\Dispatch\ClientMessageDispatcher;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class ClientMessageDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('log', new class
        {
            public function info(...$args): void {}

            public function warning(...$args): void {}

            public function error(...$args): void {}
        });

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_dispatches_valid_client_message_to_resolved_handler(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $handler = $this->createMock(MessageHandlerInterface::class);
        $factory = $this->createMock(MessageHandlerFactory::class);

        $message->method('getPayload')->willReturn(json_encode([
            'type' => 'subscribe',
            'data' => ['channel' => 'training.121'],
        ]));

        $factory->expects($this->once())
            ->method('create')
            ->with('subscribe', $this->isInstanceOf(\stdClass::class))
            ->willReturn($handler);

        $handler->expects($this->once())
            ->method('handle')
            ->with($connection, $message);

        $connection->expects($this->never())->method('send');

        (new ClientMessageDispatcher($factory))->dispatch($connection, $message);
    }

    public function test_sends_error_message_for_invalid_json_payload(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $factory = $this->createMock(MessageHandlerFactory::class);

        $message->method('getPayload')->willReturn('{invalid-json');

        $factory->expects($this->never())->method('create');

        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($sentMessage): bool {
                return $sentMessage instanceof ErrorMessage
                    && $sentMessage->type === 'error'
                    && $sentMessage->data['error'] === 'invalid_json';
            }));

        (new ClientMessageDispatcher($factory))->dispatch($connection, $message);
    }
}
