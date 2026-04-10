<?php

namespace App\Application\LinkMatchRoom\Exceptions;

class LinkMatchRoomException extends \RuntimeException
{
    public function __construct(private readonly string $errorCode, string $message = '')
    {
        parent::__construct($message ?: $errorCode);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
