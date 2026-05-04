<?php

namespace App\WebSockets;

use App\WebSockets\Broker\BrokerSubscriptionsBootstrapper;
use App\WebSockets\Timers\PeriodicTimerScheduler;
use Ratchet\WebSocket\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;

class TrainingWsRuntime
{
    private bool $bootstrapped = false;

    public function __construct(
        private readonly MessageComponentInterface $trainingWsServer,
        private readonly BrokerSubscriptionsBootstrapper $brokerSubscriptionsBootstrapper,
        private readonly PeriodicTimerScheduler $periodicTimerScheduler,
        private readonly MessageBrokerFactory $messageBrokerFactory,
        private readonly LoopInterface $loop,
    ) {}

    public function bootstrap(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        $messageBroker = $this->messageBrokerFactory->create();
        $this->brokerSubscriptionsBootstrapper->bootstrap($messageBroker);
        $this->periodicTimerScheduler->start();
        $this->bootstrapped = true;
    }

    public function run(string $address = '0.0.0.0:8080'): void
    {
        $this->bootstrap();

        $this->createIoServer($address);

        $nodeId = config('app.node_id');
        echo "[{$nodeId}] WebSocket server listening on {$address}".PHP_EOL;

        $this->loop->run();
    }

    private function createIoServer(string $address): void
    {
        new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($this->trainingWsServer)),
            new SocketServer($address, [], $this->loop),
            $this->loop
        );
    }
}
