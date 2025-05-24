<?php

namespace PeachPayments\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string authenticate()
 * @method static array createPaymentToken(array $paymentData)
 * @method static array createSubscription(array $subscriptionData)
 * @method static array processPayment(array $paymentData)
 * @method static array getSubscription(string $subscriptionId)
 * @method static array cancelSubscription(string $subscriptionId)
 * 
 * @see \PeachPayments\Laravel\Services\PeachPaymentsService
 */
class PeachPayments extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'peachpayments';
    }
}
