<?php

namespace Tests\Unit;

use App\Infrastructure\Metrics\WsMetricsInterface;
use App\WebSockets\Dispatch\ClientMessageDispatcher;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
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

            public function debug(...$args): void {}

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

    private function makeConnection(): ConnectionInterface
    {
        return new #[\AllowDynamicProperties] class implements ConnectionInterface
        {
            public int $resourceId = 42;

            public array $sent = [];

            public function send($data)
            {
                $this->sent[] = $data;

                return $this;
            }

            public function close(): void {}
        };
    }

    public function test_sends_error_message_for_invalid_json_payload(): void
    {
        $connection = $this->makeConnection();
        $message = $this->createMock(MessageInterface::class);
        $factory = $this->createMock(MessageHandlerFactory::class);
        $metrics = $this->createMock(WsMetricsInterface::class);

        $message->method('getPayload')->willReturn('{invalid-json');

        $metrics->expects($this->once())
            ->method('invalidJsonReceived');

        $metrics->expects($this->never())
            ->method('messageReceived');

        $factory->expects($this->never())->method('create');

        (new ClientMessageDispatcher($factory, $metrics))->dispatch($connection, $message);

        $this->assertCount(1, $connection->sent);
        $this->assertInstanceOf(ErrorMessage::class, $connection->sent[0]);
        $this->assertSame('error', $connection->sent[0]->type);
        $this->assertSame('invalid_json', $connection->sent[0]->data['error']);
    }
}
