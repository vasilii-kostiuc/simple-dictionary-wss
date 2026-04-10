<?php

namespace App\Application\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
