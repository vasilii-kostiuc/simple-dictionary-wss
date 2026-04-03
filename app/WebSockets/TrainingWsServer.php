<?php

namespace App\WebSockets;

use App\WebSockets\Broker\BrokerSubscriptionsBootstrapper;
use App\WebSockets\Dispatch\ClientMessageDispatcher;
use App\WebSockets\Lifecycle\ConnectionLifecycleService;
use App\WebSockets\Timers\PeriodicTimerScheduler;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class TrainingWsServer implements MessageComponentInterface
{
    protected ?MessageBrokerInterface $messageBroker = null;

    private bool $bootstrapped = false;

    public function __construct(
        protected readonly ConnectionLifecycleService $connectionLifecycleService,
        protected readonly ClientMessageDispatcher $clientMessageDispatcher,
        protected readonly BrokerSubscriptionsBootstrapper $brokerSubscriptionsBootstrapper,
        protected readonly PeriodicTimerScheduler $periodicTimerScheduler,
        protected readonly MessageBrokerFactory $messageBrokerFactory,
    ) {
    }

    public function boot(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        Log::info(__METHOD__);

        $this->messageBroker = $this->messageBrokerFactory->create();
        $this->brokerSubscriptionsBootstrapper->bootstrap($this->messageBroker);
        $this->periodicTimerScheduler->start();
        $this->bootstrapped = true;
    }

    /**
     * {@inheritDoc}
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->connectionLifecycleService->onOpen($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->connectionLifecycleService->onClose($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->connectionLifecycleService->onError($conn, $e);
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        $this->clientMessageDispatcher->dispatch($conn, $msg);
    }
}
