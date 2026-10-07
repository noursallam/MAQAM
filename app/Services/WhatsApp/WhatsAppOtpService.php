<?php

namespace App\Services\WhatsApp;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Customer-initiated WhatsApp verification: the customer first sends us a
 * challenge code, and only then do we reply with their OTP.
 *
 * A challenge is identified by an unguessable id, so the same flow serves
 * the web store (id kept in the session) and the mobile API (id kept by the app).
 */
class WhatsAppOtpService
{
    public const CHALLENGE_MINUTES = 10;
    public const OTP_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        protected SenderBotService $senderBot
    ) {}

    /**
     * Begin verification of a phone number and return the challenge to display.
     *
     * @return array{id: string, phone: string, code: string, expires_at: int, otp_hash: ?string, attempts: int, meta: array}
     */
    public function start(string $phone, array $meta = []): array
    {
        $challenge = [
            'id' => Str::random(48),
            'phone' => $phone,
            'code' => 'MAQAM-'.random_int(100000, 999999),
            'expires_at' => now()->addMinutes(self::CHALLENGE_MINUTES)->timestamp,
            'otp_hash' => null,
            'attempts' => 0,
            'meta' => $meta,
        ];

        $this->store($challenge);

        return $challenge;
    }

    public function find(?string $id): ?array
    {
        $challenge = $id ? Cache::get($this->key($id)) : null;

        if (! is_array($challenge) || $challenge['expires_at'] < now()->timestamp) {
            return null;
        }

        return $challenge;
    }

    /**
     * Look for the customer's challenge message; when it has arrived, reply with the OTP.
     *
     * @throws Exception
     */
    public function sendOtpIfChallengeReceived(string $id): bool
    {
        // Parallel polls must not send two different OTPs
        return (bool) Cache::lock($this->key($id).':lock', 20)->block(10, function () use ($id) {
            $challenge = $this->find($id);

            if (! $challenge) {
                return false;
            }

            if ($challenge['otp_hash'] !== null) {
                return true;
            }

            if (! $this->senderBot->hasIncomingText($challenge['phone'], $challenge['code'])) {
                return false;
            }

            $otp = (string) random_int(100000, 999999);

            $this->senderBot->sendText(
                $challenge['phone'],
                __('store.auth.wa_otp_message', ['otp' => $otp, 'minutes' => self::OTP_MINUTES])
            );

            $challenge['otp_hash'] = Hash::make($otp);
            // From here on the challenge lives exactly as long as the OTP
            $challenge['expires_at'] = now()->addMinutes(self::OTP_MINUTES)->timestamp;
            $this->store($challenge);

            return true;
        });
    }

    /**
     * Check the OTP typed by the customer. Returns the consumed challenge, or null when wrong.
     */
    public function verify(string $id, string $otp): ?array
    {
        return Cache::lock($this->key($id).':lock', 20)->block(10, function () use ($id, $otp) {
            $challenge = $this->find($id);

            if (! $challenge || $challenge['otp_hash'] === null) {
                return null;
            }

            if (! Hash::check($otp, $challenge['otp_hash'])) {
                $challenge['attempts']++;

                $challenge['attempts'] >= self::MAX_ATTEMPTS
                    ? Cache::forget($this->key($id))
                    : $this->store($challenge);

                return null;
            }

            // One-time use
            Cache::forget($this->key($id));

            return $challenge;
        });
    }

    /**
     * The linked WhatsApp number customers must message, in international digits.
     */
    public function businessNumber(): ?string
    {
        try {
            return Cache::remember('senderbot.linked_phone', now()->addMinutes(10), function () {
                $status = $this->senderBot->status();

                // Don't cache "not linked" so a fresh pairing is picked up immediately
                return $status['connected'] && $status['phone']
                    ? $status['phone']
                    : throw new Exception('WhatsApp number is not linked.');
            });
        } catch (Exception $e) {
            report($e);

            return null;
        }
    }

    private function store(array $challenge): void
    {
        Cache::put(
            $this->key($challenge['id']),
            $challenge,
            max(1, $challenge['expires_at'] - now()->timestamp)
        );
    }

    private function key(string $id): string
    {
        return 'wa_otp:'.hash('sha256', $id);
    }
}
