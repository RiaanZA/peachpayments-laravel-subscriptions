<?php

namespace PeachPayments\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use PeachPayments\Laravel\PeachPaymentsServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for testing
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PeachPaymentsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up test configuration
        $app['config']->set('peachpayments', [
            'environment' => 'test',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'webhook_secret' => 'test_webhook_secret',
            'default_currency' => 'ZAR',
            'subscription' => [
                'trial_days' => 0,
                'grace_days' => 7,
                'payment_attempts' => 3,
            ],
        ]);
    }
}
