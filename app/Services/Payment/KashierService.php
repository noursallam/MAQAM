<?php

namespace App\Services\Payment;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KashierService
{
    private string $mid;
    private string $paymentApiKey;
    private string $secretKey;
    private string $currency;
    private string $apiUrl;
    private string $checkoutUrl;
    private string $mode;
    private bool $sslVerify;

    public function __construct()
    {
        $this->mode = config('kashier.mode', 'test');
        $this->mid = (string) config('kashier.mid', '');
        $this->paymentApiKey = (string) config('kashier.payment_api_key', '');
        $this->secretKey = (string) config('kashier.secret_key', '');
        $this->currency = (string) config('kashier.currency', 'EGP');
        $this->apiUrl = rtrim((string) config('kashier.api_url', 'https://test-api.kashier.io'), '/');
        $this->checkoutUrl = rtrim((string) config('kashier.checkout_url', 'https://payments.kashier.io'), '/');
        $this->sslVerify = (bool) config('kashier.ssl_verify', false);
    }

    /**
     * Create a Kashier Payment Session for hosted checkout.
     *
     * @return array{sessionId: string, sessionUrl: string, raw: array}
     * @throws Exception
     */
    public function createPaymentSession(Order $order, string $locale = 'ar'): array
    {
        $amount = number_format((float) $order->total_amount, 2, '.', '');
        $customerRef = 'CUST-'.($order->user_id ?: 'GUEST-'.$order->id);
        $customerEmail = $order->user?->email ?: ($order->shippingAddress?->phone ? $order->shippingAddress->phone.'@customer.maqam-eg.com' : 'customer@maqam-eg.com');

        $redirectUrl = (string) config('kashier.merchant_redirect') ?: route('payment.kashier.callback');
        $webhookUrl = (string) config('kashier.server_webhook') ?: route('payment.kashier.webhook');

        // Kashier schema validator rejects port numbers (e.g. :8000)
        $redirectUrl = preg_replace('/:\d+/', '', $redirectUrl);
        $webhookUrl = preg_replace('/:\d+/', '', $webhookUrl);

        $payload = [
            'amount' => $amount,
            'currency' => $this->currency,
            'order' => (string) $order->order_number,
            'merchantId' => $this->mid,
            'merchantRedirect' => $redirectUrl,
            'serverWebhook' => $webhookUrl,
            'display' => in_array($locale, ['ar', 'en'], true) ? $locale : 'ar',
            'type' => 'one-time',
            'allowedMethods' => 'card,wallet',
            'brandColor' => '#C5A059',
            'customer' => [
                'reference' => $customerRef,
                'email' => $customerEmail,
            ],
            'description' => "MAQAM Order #{$order->order_number}",
        ];

        Log::info('Kashier: Initiating payment session', [
            'order_number' => $order->order_number,
            'amount' => $amount,
            'endpoint' => "{$this->apiUrl}/v3/payment/sessions",
        ]);

        $client = Http::withHeaders([
            'Authorization' => $this->secretKey,
            'api-key' => $this->paymentApiKey,
            'Content-Type' => 'application/json',
        ]);

        if (! $this->sslVerify) {
            $client = $client->withoutVerifying();
        }

        $response = $client->post("{$this->apiUrl}/v3/payment/sessions", $payload);

        if (! $response->successful()) {
            $errorData = $response->json() ?? [];

            // If an active session already exists for this order, reuse the returned sessionUrl
            if (! empty($errorData['sessionUrl'])) {
                $sessionUrl = (string) $errorData['sessionUrl'];
                preg_match('#/session/([^/?]+)#', $sessionUrl, $matches);
                $sessionId = $matches[1] ?? (string) $order->order_number;

                Log::info('Kashier: Reusing existing session for order', [
                    'order_number' => $order->order_number,
                    'session_id' => $sessionId,
                ]);

                return [
                    'sessionId' => (string) $sessionId,
                    'sessionUrl' => $sessionUrl,
                    'raw' => $errorData,
                ];
            }

            Log::error('Kashier: Session creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            $errorMsg = $errorData['messages']['ar']
                ?? $errorData['messages']['en']
                ?? $errorData['message']
                ?? 'Payment gateway error';

            throw new Exception(__('store.checkout.kashier_error', [
                'error' => $errorMsg,
            ]));
        }

        $data = $response->json();
        $sessionData = $data['data'] ?? $data['session'] ?? $data;

        $sessionId = $sessionData['_id'] ?? $sessionData['sessionId'] ?? $sessionData['id'] ?? null;
        $sessionUrl = $sessionData['sessionUrl'] ?? null;

        if (empty($sessionUrl) || empty($sessionId)) {
            Log::error('Kashier: Unexpected response structure', ['data' => $data]);
            throw new Exception('Invalid response from Kashier payment gateway.');
        }

        return [
            'sessionId' => (string) $sessionId,
            'sessionUrl' => (string) $sessionUrl,
            'raw' => $data,
        ];
    }

    /**
     * Validate the HMAC-SHA256 signature appended by Kashier on redirect callback.
     */
    public function validateRedirectSignature(array $query): bool
    {
        $fields = [
            'paymentStatus',
            'cardDataToken',
            'maskedCard',
            'merchantOrderId',
            'orderId',
            'cardBrand',
            'orderReference',
            'transactionId',
            'amount',
            'currency',
        ];

        $parts = [];
        foreach ($fields as $field) {
            $val = $query[$field] ?? 'null';
            $parts[] = "{$field}={$val}";
        }

        $payload = implode('&', $parts);
        $expectedSignature = hash_hmac('sha256', $payload, $this->paymentApiKey, false);

        $provided = (string) ($query['signature'] ?? '');

        return hash_equals(strtolower($expectedSignature), strtolower($provided));
    }

    /**
     * Validate the HMAC-SHA256 webhook signature (x-kashier-signature).
     */
    public function validateWebhookSignature(array $jsonPayload, string $receivedSignature): bool
    {
        if (empty($jsonPayload['data']) || empty($jsonPayload['data']['signatureKeys']) || ! is_array($jsonPayload['data']['signatureKeys'])) {
            return false;
        }

        $data = $jsonPayload['data'];
        $keys = $data['signatureKeys'];
        sort($keys);

        $pairs = [];
        foreach ($keys as $key) {
            $val = $data[$key] ?? '';
            // RFC 3986 URL-encoding on values only
            $pairs[] = $key.'='.rawurlencode((string) $val);
        }

        $signaturePayload = implode('&', $pairs);
        $calculated = hash_hmac('sha256', $signaturePayload, $this->paymentApiKey, false);

        return hash_equals(strtolower($calculated), strtolower($receivedSignature));
    }

    /**
     * Query session state from Kashier API.
     */
    public function verifySession(string $sessionId): array
    {
        $client = Http::withHeaders([
            'Authorization' => $this->secretKey,
        ]);

        if (! $this->sslVerify) {
            $client = $client->withoutVerifying();
        }

        $response = $client->get("{$this->apiUrl}/v3/payment/sessions/{$sessionId}/payment");

        return $response->json() ?? [];
    }
}
