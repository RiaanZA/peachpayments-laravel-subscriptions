<?php

namespace PeachPayments\Laravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PeachPayments\Laravel\Enums\SubscriptionStatus;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'peach_subscription_id',
        'plan_id',
        'status',
        'payment_method_token',
        'amount',
        'currency',
        'billing_cycle',
        'starts_at',
        'trial_ends_at',
        'ends_at',
        'next_billing_date',
        'last_payment_date',
        'last_payment_status',
        'payment_attempts',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE->value &&
               !$this->isCancelled() &&
               !$this->isExpired();
    }

    /**
     * Determine if the subscription is cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED->value ||
               ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Determine if the subscription is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Cancel the subscription.
     *
     * @param  bool  $immediately
     * @return $this
     */
    public function cancel(bool $immediately = false)
    {
        if ($immediately) {
            $this->markAsCancelled();
        } else {
            $this->update([
                'ends_at' => $this->billing_cycle_ends_at,
            ]);
        }

        return $this;
    }

    /**
     * Mark the subscription as cancelled.
     *
     * @return $this
     */
    public function markAsCancelled()
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED->value,
            'ends_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the subscription as active.
     *
     * @return $this
     */
    public function markAsActive()
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => null,
        ]);

        return $this;
    }
}
