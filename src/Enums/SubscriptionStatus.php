<?php

namespace PeachPayments\Laravel\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case UNPAID = 'unpaid';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case PENDING = 'pending';
    case TRIALING = 'trialing';
    case INCOMPLETE = 'incomplete';
    case INCOMPLETE_EXPIRED = 'incomplete_expired';
    case PAUSED = 'paused';

    /**
     * Get the display name of the status.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PAST_DUE => 'Past Due',
            self::UNPAID => 'Unpaid',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
            self::PENDING => 'Pending',
            self::TRIALING => 'Trial',
            self::INCOMPLETE => 'Incomplete',
            self::INCOMPLETE_EXPIRED => 'Incomplete Expired',
            self::PAUSED => 'Paused',
        };
    }

    /**
     * Get all the status values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
