<?php

namespace App\Services\Push;

use Exception;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * Requests to FCM in flight at once. FCM has no multicast in HTTP v1, so a
     * broadcast is one request per device; running them side by side keeps a
     * large audience to seconds instead of minutes.
     */
    public const CONCURRENCY = 25;

    /**
     * Send one notification to one device.
     *
     * @param  array<string, scalar>  $data  extra key/values delivered to the app
     * @return self::SENT|self::INVALID_TOKEN|self::FAILED
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): string
    {
        return $this->sendMany([$deviceToken], $title, $body, $data)[$deviceToken];
    }

    /**
     * Send the same notification to many devices, several at a time.
     *
     * @param  list<string>  $deviceTokens
     * @param  array<string, scalar>  $data
     * @return array<string, self::SENT|self::INVALID_TOKEN|self::FAILED>  keyed by token
     */
    public function sendMany(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $deviceTokens = array_values(array_unique($deviceTokens));
        $credentials = $this->credentials();

        if (! $credentials) {
            return array_fill_keys($deviceTokens, self::FAILED);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send";
        $accessToken = $this->accessToken($credentials);
        $results = [];

        foreach (array_chunk($deviceTokens, self::CONCURRENCY) as $batch) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $token) => $pool->as($token)->withToken($accessToken)->timeout(15)
                    ->post($url, ['message' => $this->message($token, $title, $body, $data)]),
                $batch,
            ));

            foreach ($batch as $token) {
                $results[$token] = $this->outcome($responses[$token] ?? null);
            }
        }

        return $results;
    }

    /**
     * @param  array<string, scalar>  $data
     */
    private function message(string $token, string $title, string $body, array $data): array
    {
        return [
            'token' => $token,
            'notification' => ['title' => $title, 'body' => $body],
            // FCM data values must all be strings
            'data' => (object) array_map('strval', $data),
            'android' => ['priority' => 'high'],
            'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
        ] + $this->webpush();
    }

    /**
     * Browser pushes open the store when clicked. FCM only accepts an https link.
     */
    private function webpush(): array
    {
        $link = url('/');

        return str_starts_with($link, 'https://')
            ? ['webpush' => ['fcm_options' => ['link' => $link]]]
            : [];
    }

    /**
     * @return self::SENT|self::INVALID_TOKEN|self::FAILED
     */
    private function outcome(mixed $response): string
    {
        // A connection error comes back as an exception instead of a response
        if (! $response instanceof Response) {
            Log::warning('FCM: send failed', ['error' => $response instanceof Throwable ? $response->getMessage() : 'no response']);

            return self::FAILED;
        }

        if ($response->successful()) {
            return self::SENT;
        }

        $error = $response->json('error.details.0.errorCode') ?? $response->json('error.status');

        if ($this->tokenIsDead($response->status(), (string) $error, (string) $response->json('error.message'))) {
            return self::INVALID_TOKEN;
        }

        Log::warning('FCM: send failed', ['http_status' => $response->status(), 'error' => $error]);

        return self::FAILED;
    }

    /**
     * The token will never work again: the app was uninstalled, the token
     * rotated, it belongs to another Firebase project, or it is not a token.
     */
    private function tokenIsDead(int $status, string $error, string $message): bool
    {
        return $status === 404
            || in_array($error, ['UNREGISTERED', 'SENDER_ID_MISMATCH'], true)
            || ($error === 'INVALID_ARGUMENT' && str_contains(strtolower($message), 'registration token'));
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
