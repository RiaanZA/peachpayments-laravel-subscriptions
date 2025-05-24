<?php

namespace PeachPayments\Laravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PeachPayments\Laravel\Models\Subscription;
use PeachPayments\Laravel\Services\PeachPaymentsService;

class ProcessRecurringPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peachpayments:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring subscription payments that are due';

    /**
     * The PeachPayments service instance.
     *
     * @var \PeachPayments\Laravel\Services\PeachPaymentsService
     */
    protected $peachPayments;

    /**
     * Create a new command instance.
     *
     * @param  \PeachPayments\Laravel\Services\PeachPaymentsService  $peachPayments
     * @return void
     */
    public function __construct(PeachPaymentsService $peachPayments)
    {
        parent::__construct();
        $this->peachPayments = $peachPayments;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to process recurring payments...');
        
        $now = now();
        $processed = 0;
        $failed = 0;

        // Get subscriptions that are due for payment
        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('next_billing_date', '<=', $now)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->with('user')
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to process.");

        foreach ($subscriptions as $subscription) {
            try {
                $this->info("Processing subscription #{$subscription->id} for user #{$subscription->user_id}");
                
                // Process the payment through Peach Payments
                $paymentData = [
                    'token' => $subscription->payment_method_token,
                    'amount' => (int) ($subscription->amount * 100), // Convert to cents
                    'currency' => $subscription->currency,
                    'description' => "Subscription payment for plan: {$subscription->plan_id}",
                    'merchantTransactionId' => 'sub_' . $subscription->id . '_' . now()->timestamp,
                    'createRegistration' => false, // Don't create a new registration
                ];

                $response = $this->peachPayments->processPayment($paymentData);

                if ($response && $this->isSuccessfulPayment($response)) {
                    // Update subscription with new billing period
                    $billingCycleEnd = $this->calculateNextBillingDate($subscription);
                    
                    $subscription->update([
                        'last_payment_date' => $now,
                        'last_payment_status' => 'succeeded',
                        'next_billing_date' => $billingCycleEnd,
                        'payment_attempts' => 0,
                    ]);

                    $this->info("Successfully processed payment for subscription #{$subscription->id}");
                    $processed++;
                    
                    // Dispatch payment success event
                    // event(new PaymentSucceeded($subscription, $response));
                } else {
                    $this->handleFailedPayment($subscription, $response);
                    $this->error("Failed to process payment for subscription #{$subscription->id}");
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Error processing subscription #{$subscription->id}: " . $e->getMessage());
                $this->error("Error processing subscription #{$subscription->id}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Processed {$processed} payments successfully, {$failed} failed.");
        return 0;
    }

    /**
     * Determine if the payment was successful.
     *
     * @param  array  $response
     * @return bool
     */
    protected function isSuccessfulPayment(array $response): bool
    {
        // Adjust these conditions based on Peach Payments API response structure
        return isset($response['result']['code']) && 
               in_array($response['result']['code'], ['000.000.000', '000.100.110']);
    }

    /**
     * Handle a failed payment.
     *
     * @param  \PeachPayments\Laravel\Models\Subscription  $subscription
     * @param  array|null  $response
     * @return void
     */
    protected function handleFailedPayment(Subscription $subscription, ?array $response): void
    {
        $attempts = $subscription->payment_attempts + 1;
        $maxAttempts = config('peachpayments.subscription.payment_attempts', 3);
        
        $updateData = [
            'last_payment_status' => 'failed',
            'payment_attempts' => $attempts,
        ];
        
        if ($attempts >= $maxAttempts) {
            $updateData['status'] = 'past_due';
            // Optionally cancel the subscription after max attempts
            // $subscription->cancel();
        }
        
        $subscription->update($updateData);
        
        // Dispatch payment failed event
        // event(new PaymentFailed($subscription, $response, $attempts));
        
        Log::warning("Payment failed for subscription #{$subscription->id}", [
            'attempt' => $attempts,
            'max_attempts' => $maxAttempts,
            'response' => $response,
        ]);
    }
    
    /**
     * Calculate the next billing date based on the subscription's billing cycle.
     *
     * @param  \PeachPayments\Laravel\Models\Subscription  $subscription
     * @return \Carbon\Carbon
     */
    protected function calculateNextBillingDate(Subscription $subscription): Carbon
    {
        $now = now();
        
        switch ($subscription->billing_cycle) {
            case 'day':
                return $now->copy()->addDay();
            case 'week':
                return $now->copy()->addWeek();
            case 'year':
                return $now->copy()->addYear();
            case 'month':
            default:
                return $now->copy()->addMonth();
        }
    }
}
