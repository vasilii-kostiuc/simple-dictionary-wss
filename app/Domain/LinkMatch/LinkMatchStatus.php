<?php

namespace App\Domain\LinkMatch;

enum LinkMatchStatus: string
{
    case Pending = 'pending';
    case Expired = 'expired';
}