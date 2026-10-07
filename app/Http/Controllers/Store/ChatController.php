<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * The website's support assistant. It is open to guests, so every message must pass
 * several independent bot checks before it is allowed to cost an AI request:
 *
 *  - a session with a CSRF token (plain scripted POSTs fail)
 *  - a hidden honeypot field (form-filling bots fill it)
 *  - a single-use proof-of-work puzzle solved in the browser (needs real JavaScript and CPU time)
 *  - a minimum time between receiving the puzzle and answering it
 *  - limits per IP per minute, per visitor per day, and for the whole site per day
 *
 * The conversation is kept in the session, so a client cannot forge earlier assistant turns.
 */
class ChatController extends Controller
{
    private const POW_BITS = 15;
    private const POW_TTL_SECONDS = 600;
    private const MIN_SOLVE_MILLISECONDS = 400;
    private const VISITOR_MESSAGES_PER_DAY = 25;
    private const IP_MESSAGES_PER_DAY = 60;
    private const SITE_MESSAGES_PER_DAY = 2000;
    private const HISTORY_TURNS = 10;

    /**
     * Hand the browser a fresh puzzle (called when the chat panel opens).
     */
    public function challenge(Request $request): JsonResponse
    {
        return response()->json(['challenge' => $this->issueChallenge($request)]);
    }

    public function send(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:500'],
            'counter' => ['required', 'integer', 'min:0'],
            'website' => ['nullable', 'string'],
        ]);

        // Answered as JSON here: web routes would otherwise redirect back on a validation error
        if ($validator->fails()) {
            return $this->refuse($request, __('store.chat.verify_failed'), 422);
        }

        $validated = $validator->validated();

        // Honeypot: people never see this field. Answer as if it worked so the bot learns nothing.
        if (filled($validated['website'] ?? null)) {
            return response()->json(['reply' => __('store.chat.fallback'), 'challenge' => $this->issueChallenge($request)]);
        }

        if (blank($request->userAgent()) || ! $this->solvedChallenge($request, (int) $validated['counter'])) {
            return $this->refuse($request, __('store.chat.verify_failed'), 422);
        }

        if (! $chatbot->isConfigured()) {
            return $this->refuse($request, __('store.chat.unavailable'), 503);
        }

        $visitorKey = 'web-chat:visitor:'.$request->session()->getId();
        $ipKey = 'web-chat:ip:'.$request->ip();
        $siteKey = 'web-chat:site';

        if (RateLimiter::tooManyAttempts($visitorKey, self::VISITOR_MESSAGES_PER_DAY)
            || RateLimiter::tooManyAttempts($ipKey, self::IP_MESSAGES_PER_DAY)
            || RateLimiter::tooManyAttempts($siteKey, self::SITE_MESSAGES_PER_DAY)) {
            return $this->refuse($request, __('store.chat.limit_reached'), 429);
        }

        foreach ([$visitorKey, $ipKey, $siteKey] as $key) {
            RateLimiter::hit($key, 24 * 60 * 60);
        }

        $history = $request->session()->get('chat_history', []);

        try {
            $reply = $chatbot->reply(
                $validated['message'],
                $history,
                $request->user()?->customer?->loadMissing('rank'),
                app()->getLocale(),
            );
        } catch (Throwable $e) {
            report($e);

            return $this->refuse($request, __('store.chat.unavailable'), 503);
        }

        $history[] = ['role' => 'user', 'text' => $validated['message']];
        $history[] = ['role' => 'assistant', 'text' => $reply];
        $request->session()->put('chat_history', array_slice($history, -self::HISTORY_TURNS));

        return response()->json(['reply' => $reply, 'challenge' => $this->issueChallenge($request)]);
    }

    /**
     * @return array{nonce: string, bits: int}
     */
    private function issueChallenge(Request $request): array
    {
        $nonce = Str::random(32);

        $request->session()->put('chat_pow', ['nonce' => $nonce, 'issued_at' => microtime(true)]);

        return ['nonce' => $nonce, 'bits' => self::POW_BITS];
    }

    /**
     * The puzzle: find a counter so that sha256("nonce:counter") starts with POW_BITS zero bits.
     * Each puzzle is consumed on first use, right or wrong, so a solution can never be replayed.
     */
    private function solvedChallenge(Request $request, int $counter): bool
    {
        $pow = $request->session()->pull('chat_pow');

        if (! is_array($pow) || empty($pow['nonce'])) {
            return false;
        }

        $age = microtime(true) - (float) $pow['issued_at'];

        if ($age > self::POW_TTL_SECONDS || $age * 1000 < self::MIN_SOLVE_MILLISECONDS) {
            return false;
        }

        $hash = hash('sha256', $pow['nonce'].':'.$counter, true);
        $bits = '';
        foreach (str_split(substr($hash, 0, 4)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return strspn($bits, '0') >= self::POW_BITS;
    }

    private function refuse(Request $request, string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message, 'challenge' => $this->issueChallenge($request)], $status);
    }
}
