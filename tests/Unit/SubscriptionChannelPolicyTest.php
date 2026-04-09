<?php

namespace Tests\Unit;

use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use PHPUnit\Framework\TestCase;

class SubscriptionChannelPolicyTest extends TestCase
{
    public function test_matches_supported_dynamic_and_static_channels(): void
    {
        $policy = new SubscriptionChannelPolicy;

        $this->assertTrue($policy->canSubscribe('training.121'));
        $this->assertTrue($policy->canSubscribe('match.123'));
        $this->assertTrue($policy->canSubscribe('matchmaking.queue'));
        $this->assertTrue($policy->canUnsubscribe('training.121'));
        $this->assertTrue($policy->canUnsubscribe('match.123'));
    }

    public function test_rejects_unknown_channels(): void
    {
        $policy = new SubscriptionChannelPolicy;

        $this->assertFalse($policy->canSubscribe('profile.1'));
        $this->assertFalse($policy->canUnsubscribe('admin.secret'));
    }
}
