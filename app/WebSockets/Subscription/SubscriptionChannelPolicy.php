<?php

namespace App\WebSockets\Subscription;

use App\WebSockets\Enums\SubscriptionChannelPattern;

class SubscriptionChannelPolicy
{
    public function canSubscribe(string $channel): bool
    {
        return $this->matches($channel);
    }

    public function canUnsubscribe(string $channel): bool
    {
        return $this->matches($channel);
    }

    public function matches(string $channel): bool
    {
        foreach (SubscriptionChannelPattern::cases() as $pattern) {
            $value = $pattern->value;

            if (str_ends_with($value, '.*')) {
                $prefix = substr($value, 0, -2);

                if ($channel === $prefix || str_starts_with($channel, $prefix.'.')) {
                    return true;
                }

                continue;
            }

            if ($channel === $value) {
                return true;
            }
        }

        return false;
    }
}
