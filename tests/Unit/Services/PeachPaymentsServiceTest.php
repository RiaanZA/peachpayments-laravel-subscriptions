<?php

namespace PeachPayments\Laravel\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PeachPayments\Laravel\Services\PeachPaymentsService;
use PeachPayments\Laravel\Tests\TestCase;

class PeachPaymentsServiceTest extends TestCase
{
    private $client;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock HTTP client
        $this->client = Mockery::mock(Client::class);
        
        // Initialize the service with test credentials
        $this->service = new PeachPaymentsService(
            'test_client_id',
            'test_client_secret',
            'test'
        );
        
        // Replace the HTTP client with our mock
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $this->client);
    }

    /** @test */
    public function it_can_authenticate()
    {
        // Mock the authentication response
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['access_token' => 'test_access_token'])
        );
        
        $this->client->shouldReceive('post')
            ->once()
            ->with('v1/auth/token', [
                'auth' => ['test_client_id', 'test_client_secret'],
                'json' => ['grant_type' => 'client_credentials'],
            ])
            ->andReturn($response);
        
        $token = $this->service->authenticate();
        
        $this->assertEquals('test_access_token', $token);
    }
    
    /** @test */
    public function it_can_create_a_payment_token()
    {
        // Mock the authentication
        $this->mockAuthentication();
        
        // Mock the payment token response
        $response = new Response(
            201,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'token_123',
                'card' => [
                    'last4' => '4242',
                    'expiryMonth' => '12',
                    'expiryYear' => '2025',
                ]
            ])
        );
        
        $this->client->shouldReceive('request')
            ->once()
            ->with('post', 'v1/tokens', Mockery::any())
            ->andReturn($response);
        
        $tokenData = $this->service->createPaymentToken([
            'card' => [
                'number' => '4111111111111111',
                'expiryMonth' => '12',
                'expiryYear' => '2025',
                'cvv' => '123',
            ]
        ]);
        
        $this->assertEquals('token_123', $tokenData['id']);
        $this->assertEquals('4242', $tokenData['card']['last4']);
    }
    
    /** @test */
    public function it_can_create_a_subscription()
    {
        // Mock the authentication
        $this->mockAuthentication();
        
        // Mock the subscription response
        $response = new Response(
            201,
            ['Content-Type' => 'application/json'],
            json_encode([
                'id' => 'sub_123',
                'status' => 'active',
                'amount' => 999,
                'currency' => 'ZAR',
                'billingCycle' => 'monthly',
            ])
        );
        
        $this->client->shouldReceive('request')
            ->once()
            ->with('post', 'v1/subscriptions', Mockery::any())
            ->andReturn($response);
        
        $subscription = $this->service->createSubscription([
            'paymentMethodToken' => 'token_123',
            'amount' => 999,
            'currency' => 'ZAR',
            'billingCycle' => 'monthly',
        ]);
        
        $this->assertEquals('sub_123', $subscription['id']);
        $this->assertEquals('active', $subscription['status']);
    }
    
    /**
     * Mock the authentication response.
     */
    private function mockAuthentication()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['access_token' => 'test_access_token'])
        );
        
        $this->client->shouldReceive('post')
            ->once()
            ->andReturn($response);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
