<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\SystemSetting;
use App\Services\ChatbotService;
use App\Support\LegalContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Throwable;

class AppController extends ApiController
{
    private const CHAT_MESSAGES_PER_DAY = 60;

    /**
     * Tells the app whether it must (or may) update. Call it at launch.
     */
    public function config(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'version' => ['required', 'regex:/^\d+(\.\d+){0,3}$/'],
        ]);

        $platform = $validated['platform'];
        $minimum = (string) SystemSetting::getValue("app_min_version_{$platform}", '1.0.0');
        $latest = (string) SystemSetting::getValue("app_latest_version_{$platform}", '1.0.0');

        return $this->ok([
            'update_required' => version_compare($validated['version'], $minimum, '<'),
            'update_available' => version_compare($validated['version'], $latest, '<'),
            'minimum_version' => $minimum,
            'latest_version' => $latest,
            'store_url' => (string) SystemSetting::getValue("app_store_url_{$platform}", '') ?: null,
        ]);
    }

    public function faq(): JsonResponse
    {
        return $this->ok(collect([1, 2, 3, 4, 5, 6, 7])->map(fn ($i) => [
            'id' => $i,
            'question' => __("store.faq.q{$i}"),
            'answer' => __("store.faq.a{$i}"),
        ])->values());
    }

    /**
     * A legal page as HTML, for a WebView or an HTML widget.
     */
    public function page(string $slug): JsonResponse
    {
        if (! in_array($slug, LegalContent::PAGES, true)) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        return $this->ok([
            'slug' => $slug,
            'title' => __("store.legal.{$slug}_title"),
            'lead' => __("store.legal.{$slug}_lead"),
            'html' => trim(preg_replace('/^\s+/m', '', LegalContent::body($slug))),
            'url' => route('store.'.$slug),
        ]);
    }

    /**
     * One turn with the support assistant. The app keeps the conversation and sends recent turns back.
     */
    public function chat(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.text' => ['required', 'string', 'max:2000'],
        ]);

        if (! $chatbot->isConfigured()) {
            throw new ApiException('CHATBOT_UNAVAILABLE', __('api.chat.unavailable'), 503);
        }

        // A daily allowance per customer keeps the paid AI key from being drained
        $key = 'chat-daily:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::CHAT_MESSAGES_PER_DAY)) {
            throw new ApiException('CHAT_LIMIT_REACHED', __('api.chat.limit_reached'), 429);
        }

        RateLimiter::hit($key, 24 * 60 * 60);

        try {
            $reply = $chatbot->reply(
                $validated['message'],
                $validated['history'] ?? [],
                $this->customer($request)->loadMissing('rank'),
                app()->getLocale(),
            );
        } catch (Throwable $e) {
            report($e);

            throw new ApiException('CHATBOT_UNAVAILABLE', __('api.chat.unavailable'), 503);
        }

        return $this->ok([
            'reply' => $reply,
            'messages_left_today' => RateLimiter::remaining($key, self::CHAT_MESSAGES_PER_DAY),
        ]);
    }
}
