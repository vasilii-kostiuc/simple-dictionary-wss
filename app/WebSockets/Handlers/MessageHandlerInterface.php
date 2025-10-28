<?php

namespace App\WebSockets\Handlers;

interface MessageHandlerInterface
{
    public function handle($from, $msg);

}
