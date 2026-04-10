<?php

namespace App\Infrastructure\Shared;

use App\Application\Contracts\EventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
