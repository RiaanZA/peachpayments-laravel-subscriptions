<?php

namespace PeachPayments\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PeachPayments\Laravel\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a Peach Payments webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        
        // Verify the webhook signature
        if (! $this->isValidWebhook($request)) {
            Log::warning('PeachPayments webhook signature verification failed', [
                'payload' => $payload,
                'headers' => $request->headers->all(),
            ]);
            
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $eventType = $payload['event'] ?? null;
        
        Log::info("PeachPayments webhook received: {$eventType}", $payload);

        try {
            if (method_exists($this, $method = 'handle' . str_replace('.', '', $eventType))) {
                return $this->{$method}($payload);
            }

            Log::warning("Unhandled PeachPayments webhook: {$eventType}");
            
            return response('Webhook Handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error processing PeachPayments webhook: " . $e->getMessage(), [
                'exception' => $e,
                'payload' => $payload,
            ]);
            
            return response('Webhook Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle subscription.created event.
     */
    protected function handleSubscriptionCreated(array $payload)
    {
        // Handle subscription creation
        $subscriptionData = $payload['data']['subscription'];
        
        // Update or create subscription in your database
        Subscription::updateOrCreate(
            ['peach_subscription_id' => $subscriptionData['id']],
            $this->mapSubscriptionData($subscriptionData)
        );

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Handle subscription.updated event.
     */
    protected function handleSubscriptionUpdated(array $payload)
    {
        $subscriptionData = $payload['data']['subscription'];
        
        if ($subscription = Subscription::where('peach_subscription_id', $subscriptionData['id'])->first()) {
            $subscription->update($this->mapSubscriptionData($subscriptionData));
        }

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Handle subscription.cancelled event.
     */
    protected function handleSubscriptionCancelled(array $payload)
    {
        $subscriptionData = $payload['data']['subscription'];
        
        if ($subscription = Subscription::where('peach_subscription_id', $subscriptionData['id'])->first()) {
            $subscription->update([
                'status' => 'cancelled',
                'ends_at' => now(),
            ]);
        }

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Handle payment.succeeded event.
     */
    protected function handlePaymentSucceeded(array $payload)
    {
        $paymentData = $payload['data']['payment'];
        
        if (isset($paymentData['subscription_id'])) {
            $subscription = Subscription::where('peach_subscription_id', $paymentData['subscription_id'])->first();
            
            if ($subscription) {
                // Update subscription with latest payment info
                $subscription->update([
                    'last_payment_date' => now(),
                    'last_payment_status' => 'succeeded',
                    'next_billing_date' => $paymentData['next_billing_date'] ?? null,
                    'payment_attempts' => 0, // Reset payment attempts on success
                ]);
                
                // You might want to trigger a payment succeeded event here
                // event(new PaymentSucceeded($subscription, $paymentData));
            }
        }

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Handle payment.failed event.
     */
    protected function handlePaymentFailed(array $payload)
    {
        $paymentData = $payload['data']['payment'];
        
        if (isset($paymentData['subscription_id'])) {
            $subscription = Subscription::where('peach_subscription_id', $paymentData['subscription_id'])->first();
            
            if ($subscription) {
                $attempts = $subscription->payment_attempts + 1;
                
                $subscription->update([
                    'last_payment_status' => 'failed',
                    'payment_attempts' => $attempts,
                    'status' => $attempts >= config('peachpayments.subscription.payment_attempts', 3) 
                        ? 'past_due' 
                        : $subscription->status,
                ]);
                
                // You might want to trigger a payment failed event here
                // event(new PaymentFailed($subscription, $paymentData, $attempts));
            }
        }

        return response('Webhook Handled', Response::HTTP_OK);
    }

    /**
     * Map Peach Payments subscription data to our database fields.
     */
    protected function mapSubscriptionData(array $subscriptionData): array
    {
        return [
            'status' => $subscriptionData['status'] ?? 'inactive',
            'plan_id' => $subscriptionData['plan_id'] ?? null,
            'amount' => $subscriptionData['amount'] / 100, // Convert from cents
            'currency' => $subscriptionData['currency'] ?? config('peachpayments.default_currency', 'ZAR'),
            'billing_cycle' => $subscriptionData['billing_cycle'] ?? 'monthly',
            'starts_at' => $subscriptionData['start_date'] ?? null,
            'trial_ends_at' => $subscriptionData['trial_end'] ?? null,
            'ends_at' => $subscriptionData['end_date'] ?? null,
            'next_billing_date' => $subscriptionData['next_billing_date'] ?? null,
            'metadata' => $subscriptionData['metadata'] ?? [],
        ];
    }

    /**
     * Verify the incoming webhook signature.
     */
    protected function isValidWebhook(Request $request): bool
    {
        $signature = $request->header('X-Peach-Signature');
        
        if (! $signature) {
            return false;
        }
        
        $payload = $request->getContent();
        $secret = config('peachpayments.webhook_secret');
        $expected = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expected, $signature);
    }
}
