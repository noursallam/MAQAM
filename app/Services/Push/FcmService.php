<?php

namespace App\Services\Push;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1) sender, authenticated with a service-account key.
 */
class FcmService
{
    public const SENT = 'sent';
    public const INVALID_TOKEN = 'invalid_token';
    public const FAILED = 'failed';

    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * Send one notification to one device.
     *
     * @param  array<string, scalar>  $data  extra key/values delivered to the app
     * @return self::SENT|self::INVALID_TOKEN|self::FAILED
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): string
    {
        $credentials = $this->credentials();

        if (! $credentials) {
            return self::FAILED;
        }

        $response = Http::withToken($this->accessToken($credentials))
            ->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    // FCM data values must all be strings
                    'data' => (object) array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);

        if ($response->successful()) {
            return self::SENT;
        }

        $error = $response->json('error.details.0.errorCode') ?? $response->json('error.status');

        // The app was uninstalled or the token rotated
        if ($response->status() === 404 || $error === 'UNREGISTERED') {
            return self::INVALID_TOKEN;
        }

        Log::warning('FCM: send failed', ['http_status' => $response->status(), 'error' => $error]);

        return self::FAILED;
    }

    private function credentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = (string) config('services.firebase.credentials');

        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        // Must be a service-account key, not the app's google-services.json
        if (! is_array($json) || empty($json['private_key']) || empty($json['client_email']) || empty($json['project_id'])) {
            return null;
        }

        return $this->credentials = $json;
    }

    /**
     * Short-lived OAuth token obtained by signing a JWT with the service-account key.
     *
     * @throws Exception
     */
    private function accessToken(array $credentials): string
    {
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $segments = [
                $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
                $this->base64Url(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                ])),
            ];

            if (! openssl_sign(implode('.', $segments), $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new Exception('FCM: could not sign the access token request.');
            }

            $segments[] = $this->base64Url($signature);

            $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => implode('.', $segments),
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new Exception('FCM: access token request failed ('.$response->status().').');
            }

            return $response->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
