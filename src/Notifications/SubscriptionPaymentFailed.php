<?php

namespace PeachPayments\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The subscription instance.
     *
     * @var \PeachPayments\Laravel\Models\Subscription
     */
    public $subscription;

    /**
     * The payment data.
     *
     * @var array
     */
    public $paymentData;

    /**
     * The number of attempts made.
     *
     * @var int
     */
    public $attempts;

    /**
     * Create a new notification instance.
     *
     * @param  \PeachPayments\Laravel\Models\Subscription  $subscription
     * @param  array  $paymentData
     * @param  int  $attempts
     * @return void
     */
    public function __construct($subscription, array $paymentData, int $attempts)
    {
        $this->subscription = $subscription;
        $this->paymentData = $paymentData;
        $this->attempts = $attempts;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Subscription Payment Failed')
            ->line('We were unable to process your payment for your subscription.')
            ->line('Subscription: ' . $this->subscription->plan_id)
            ->line('Amount: ' . $this->subscription->amount . ' ' . $this->subscription->currency)
            ->line('Attempt: ' . $this->attempts . ' of ' . config('peachpayments.subscription.payment_attempts', 3))
            ->action('Update Payment Method', url('/billing/payment-method'))
            ->line('We will try to process this payment again. Please ensure your payment method is up to date.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'amount' => $this->subscription->amount,
            'currency' => $this->subscription->currency,
            'attempts' => $this->attempts,
            'max_attempts' => config('peachpayments.subscription.payment_attempts', 3),
        ];
    }
}
