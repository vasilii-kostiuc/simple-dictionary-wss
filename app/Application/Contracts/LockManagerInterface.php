<?php

namespace App\Application\Contracts;

interface LockManagerInterface
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function execute(string $key, callable $callback): mixed;
}
