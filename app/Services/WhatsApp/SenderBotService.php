<?php

namespace App\Services\WhatsApp;

use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SenderBotService
{
    private string $baseUrl;
    private string $token;
    private string $sessionId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.senderbot.url', ''), '/');
        $this->token = (string) config('services.senderbot.token', '');
        $this->sessionId = (string) config('services.senderbot.session', 'maqam');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->token !== '';
    }

    /**
     * Current state of the WhatsApp session.
     *
     * @return array{exists: bool, connected: bool, status: string, phone: ?string, name: ?string}
     * @throws Exception
     */
    public function status(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/sessions/status/{$this->sessionId}");

        // Session not created yet (or not loaded in memory)
        if (in_array($response->status(), [403, 404], true)) {
            return ['exists' => false, 'connected' => false, 'status' => 'not_found', 'phone' => null, 'name' => null];
        }

        $data = $this->data($response, 'status');
        $status = strtolower((string) ($data['status'] ?? 'unknown'));
        $userInfo = is_array($data['user_info'] ?? null) ? $data['user_info'] : [];
        $jid = (string) ($userInfo['id'] ?? '');

        return [
            'exists' => true,
            // "connected" alone only means the socket is open, not that a number is paired
            'connected' => array_key_exists('valid_session', $data)
                ? (bool) $data['valid_session']
                : $status === 'authenticated',
            'status' => $status,
            'phone' => $jid !== '' ? preg_replace('/[:@].*$/', '', $jid) : null,
            'name' => $userInfo['name'] ?? null,
        ];
    }

    /**
     * Start a fresh session and return its pairing QR as an image data URI.
     *
     * @throws Exception
     */
    public function startPairing(): string
    {
        // A stale unpaired session blocks /sessions/add, so drop it first
        $this->client()->delete("{$this->baseUrl}/sessions/delete/{$this->sessionId}");

        $response = $this->client()->timeout(40)->post("{$this->baseUrl}/sessions/add", [
            'id' => $this->sessionId,
            'isLegacy' => false,
        ]);

        $data = $this->data($response, 'add session');
        $qr = (string) ($data['qr'] ?? $data['qrcode'] ?? $data['qr_code'] ?? '');

        if ($qr === '') {
            throw new Exception('Senderbot did not return a QR code.');
        }

        if (str_starts_with($qr, 'data:image')) {
            return $qr;
        }

        // Raw pairing string: render it ourselves
        return (new PngWriter)->write(new EndroidQrCode(data: $qr, size: 320, margin: 12))->getDataUri();
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        $response = $this->client()->delete("{$this->baseUrl}/sessions/delete/{$this->sessionId}");

        if (! $response->successful() && ! in_array($response->status(), [403, 404], true)) {
            $this->data($response, 'delete session');
        }
    }

    /**
     * Whether the given phone has sent us a message containing $needle recently.
     *
     * @throws Exception
     */
    public function hasIncomingText(string $phone, string $needle): bool
    {
        $jid = $this->toWhatsAppNumber($phone).'@s.whatsapp.net';
        $response = $this->client()->get("{$this->baseUrl}/chats/{$jid}", [
            'id' => $this->sessionId,
            'limit' => 25,
        ]);

        // No chat with this number yet
        if ($response->status() === 404) {
            return false;
        }

        $data = $this->data($response, 'chat history');
        $messages = is_array($data['messages'] ?? null) ? $data['messages'] : $data;

        foreach ($messages as $message) {
            if (! is_array($message) || ($message['key']['fromMe'] ?? $message['fromMe'] ?? false)) {
                continue;
            }

            $content = is_array($message['message'] ?? null) ? $message['message'] : [];
            $text = $content['conversation']
                ?? $content['extendedTextMessage']['text']
                ?? $message['text']
                ?? $message['body']
                ?? '';

            if (is_string($text) && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function sendText(string $phone, string $text): void
    {
        $response = $this->client()->post("{$this->baseUrl}/chats/send?id={$this->sessionId}", [
            'receiver' => $this->toWhatsAppNumber($phone),
            'message' => ['text' => $text],
        ]);

        $this->data($response, 'send message');
    }

    /**
     * Local Egyptian mobile (01xxxxxxxxx) to international digits (201xxxxxxxxx).
     */
    public function toWhatsAppNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '0') ? '2'.$digits : $digits;
    }

    private function client(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new Exception('Senderbot is not configured (SENDER_BOT_URL / SENDER_BOT).');
        }

        return Http::withToken($this->token)->acceptJson()->timeout(15);
    }

    /**
     * Unwrap the `{ success, message, data }` envelope.
     *
     * @throws Exception
     */
    private function data(Response $response, string $action): array
    {
        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['success'] ?? true) === false) {
            Log::warning("Senderbot: {$action} failed", [
                'http_status' => $response->status(),
                'message' => $body['message'] ?? null,
            ]);

            throw new Exception((string) ($body['message'] ?? "Senderbot request failed ({$response->status()})."));
        }

        return is_array($body['data'] ?? null) ? $body['data'] : [];
    }
}
