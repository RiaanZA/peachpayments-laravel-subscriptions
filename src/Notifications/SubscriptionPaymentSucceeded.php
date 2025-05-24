<?php

namespace PeachPayments\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentSucceeded extends Notification implements ShouldQueue
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
     * Create a new notification instance.
     *
     * @param  \PeachPayments\Laravel\Models\Subscription  $subscription
     * @param  array  $paymentData
     * @return void
     */
    public function __construct($subscription, array $paymentData)
    {
        $this->subscription = $subscription;
        $this->paymentData = $paymentData;
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
            ->subject('Payment Received - Thank You!')
            ->line('Thank you for your payment. Your subscription has been renewed.')
            ->line('Subscription: ' . $this->subscription->plan_id)
            ->line('Amount: ' . $this->subscription->amount . ' ' . $this->subscription->currency)
            ->line('Next Billing Date: ' . $this->subscription->next_billing_date->format('F j, Y'))
            ->line('Thank you for using our service!');
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
            'next_billing_date' => $this->subscription->next_billing_date,
        ];
    }
}
