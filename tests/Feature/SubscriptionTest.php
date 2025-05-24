<?php

namespace PeachPayments\Laravel\Tests\Feature;

use PeachPayments\Laravel\Models\Subscription;
use PeachPayments\Laravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_subscription()
    {
        $subscription = Subscription::create([
            'user_id' => 1,
            'plan_id' => 'test-plan',
            'status' => 'active',
            'amount' => 9.99,
            'currency' => 'ZAR',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('test-plan', $subscription->plan_id);
        $this->assertEquals('active', $subscription->status);
    }

    /** @test */
    public function it_can_check_if_subscription_is_active()
    {
        $subscription = Subscription::create([
            'user_id' => 1,
            'plan_id' => 'test-plan',
            'status' => 'active',
            'amount' => 9.99,
            'currency' => 'ZAR',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isActive());
    }

    /** @test */
    public function it_can_check_if_subscription_is_cancelled()
    {
        $subscription = Subscription::create([
            'user_id' => 1,
            'plan_id' => 'test-plan',
            'status' => 'cancelled',
            'amount' => 9.99,
            'currency' => 'ZAR',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isCancelled());
    }
}
