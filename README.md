# PeachPayments Laravel Subscriptions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/peachpayments/laravel-subscriptions.svg?style=flat-square)](https://packagist.org/packages/peachpayments/laravel-subscriptions)
[![Total Downloads](https://img.shields.io/packagist/dt/peachpayments/laravel-subscriptions.svg?style=flat-square)](https://packagist.org/packages/peachpayments/laravel-subscriptions)

A Laravel package for handling Peach Payments subscriptions and recurring payments.

## Features

- 💳 Handle subscription payments with Peach Payments
- 🔄 Process recurring payments automatically
- 🛡️ Secure tokenization of payment methods
- 📅 Schedule and manage subscription billing cycles
- 🔔 Webhook handling for payment events
- 📧 Email notifications for payment events
- 🧪 Comprehensive test suite

## Requirements

- PHP 8.2 or higher
- Laravel 10.0, 11.0, or 12.0
- GuzzleHTTP 7.5 or higher

## Installation

You can install the package via Composer:

```bash
composer require peachpayments/laravel-subscriptions
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="PeachPayments\Laravel\PeachPaymentsServiceProvider" --tag="config"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add the following environment variables to your `.env` file:

```env
PEACH_ENVIRONMENT=test
PEACH_CLIENT_ID=your_client_id
PEACH_CLIENT_SECRET=your_client_secret
PEACH_WEBHOOK_SECRET=your_webhook_secret
```

## Usage

### Creating a Subscription

```php
use PeachPayments\Laravel\Facades\PeachPayments;
use PeachPayments\Laravel\Models\Subscription;

// Create a payment token
$token = PeachPayments::createPaymentToken([
    'card' => [
        'number' => '4111111111111111',
        'expiryMonth' => '12',
        'expiryYear' => '2025',
        'cvv' => '123',
    ],
]);

// Create a subscription
$subscription = Subscription::create([
    'user_id' => auth()->id(),
    'plan_id' => 'premium-monthly',
    'amount' => 9.99,
    'currency' => 'ZAR',
    'billing_cycle' => 'monthly',
    'payment_method_token' => $token['id'],
    'status' => 'active',
    'next_billing_date' => now()->addMonth(),
]);

// Or use the facade
$response = PeachPayments::createSubscription([
    'paymentMethodToken' => $token['id'],
    'amount' => 999, // In cents
    'currency' => 'ZAR',
    'billingCycle' => 'monthly',
    'startDate' => now()->format('Y-m-d'),
    'description' => 'Premium Monthly Subscription',
]);
```

### Processing Recurring Payments

Add the following to your `app/Console/Kernel.php` to process recurring payments:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('peachpayments:process-recurring')->daily();
}
```

### Webhook Handling

Add the webhook route to your `routes/api.php`:

```php
use PeachPayments\Laravel\Http\Controllers\WebhookController;

Route::post('webhooks/peachpayments', [WebhookController::class, 'handleWebhook'])
    ->name('peachpayments.webhook')
    ->middleware('peachpayments.webhook');
```

## Testing

```bash
composer test
```

## Security

If you discover any security issues, please email security@example.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)
