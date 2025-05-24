<?php

use Illuminate\Support\Facades\Route;
use PeachPayments\Laravel\Http\Controllers\WebhookController;

Route::middleware(config('peachpayments.webhook.middleware', ['api']))
    ->post(config('peachpayments.webhook.path', 'api/webhooks/peachpayments'), [WebhookController::class, 'handleWebhook'])
    ->name('peachpayments.webhook');
