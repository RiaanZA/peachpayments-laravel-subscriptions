<?php

namespace PeachPayments\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class VerifyPeachPaymentsWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Peach-Signature');
        
        if (! $signature) {
            Log::warning('Missing PeachPayments webhook signature');
            return response('Missing signature', Response::HTTP_UNAUTHORIZED);
        }
        
        $payload = $request->getContent();
        $secret = config('peachpayments.webhook_secret');
        
        if (empty($secret)) {
            Log::error('PeachPayments webhook secret is not configured');
            return response('Webhook not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        $expected = hash_hmac('sha256', $payload, $secret);
        
        if (! hash_equals($expected, $signature)) {
            Log::warning('Invalid PeachPayments webhook signature', [
                'expected' => $expected,
                'received' => $signature,
            ]);
            
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }
        
        return $next($request);
    }
}
