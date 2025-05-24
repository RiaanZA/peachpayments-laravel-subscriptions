<?php

namespace PeachPayments\Laravel;

use Illuminate\Support\ServiceProvider;
use PeachPayments\Laravel\Services\PeachPaymentsService;

class PeachPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/peachpayments.php' => config_path('peachpayments.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/peachpayments.php', 'peachpayments');

        $this->app->singleton('peachpayments', function ($app) {
            return new PeachPaymentsService(
                config('peachpayments.client_id'),
                config('peachpayments.client_secret'),
                config('peachpayments.environment', 'test')
            );
        });
    }
}
