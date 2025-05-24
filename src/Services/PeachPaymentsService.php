<?php

namespace PeachPayments\Laravel\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PeachPaymentsService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $accessToken;

    public function __construct(string $clientId, string $clientSecret, string $environment = 'test')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $environment === 'production' 
            ? 'https://api.peachpayments.com/'
            : 'https://testapi.peachpayments.com/';
            
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Authenticate with Peach Payments API
     */
    public function authenticate(): ?string
    {
        try {
            $response = $this->client->post('v1/auth/token', [
                'auth' => [$this->clientId, $this->clientSecret],
                'json' => ['grant_type' => 'client_credentials'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->accessToken = $data['access_token'];
            
            return $this->accessToken;
            
        } catch (GuzzleException $e) {
            Log::error('PeachPayments authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a payment token
     */
    public function createPaymentToken(array $paymentData): ?array
    {
        return $this->makeRequest('post', 'v1/tokens', $paymentData);
    }

    /**
     * Create a subscription
     */
    public function createSubscription(array $subscriptionData): ?array
    {
        return $this->makeRequest('post', 'v1/subscriptions', $subscriptionData);
    }

    /**
     * Process a payment
     */
    public function processPayment(array $paymentData): ?array
    {
        return $this->makeRequest('post', 'v1/payments', $paymentData);
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('get', "v1/subscriptions/{$subscriptionId}");
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): ?array
    {
        return $this->makeRequest('delete', "v1/subscriptions/{$subscriptionId}");
    }

    /**
     * Make API request with authentication
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ],
            ];

            if (!empty($data)) {
                $options['json'] = $data;
            }


            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (GuzzleException $e) {
            Log::error('PeachPayments API request failed: ' . $e->getMessage());
            return null;
        }
    }
}
