<?php

namespace App\Services;

use App\Models\Insight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiPerformanceSummary
{
    /**
     * @return array{ok: bool, text: string, from_cache?: bool, source?: string}
     */
    public function generate(array $metrics, string $locale = 'ar', bool $forceRefresh = false): array
    {
        // Always Arabic for Gemini/insights so UI parsing stays stable across admin locales.
        $locale = 'ar';
        $key = $this->insightKey($locale);
        $ttlHours = max(1, (int) ceil(((int) config('services.gemini.cache_minutes', 1440)) / 60));
        $existing = Insight::query()->where('key', $key)->first();

        if (! $forceRefresh && $existing && $existing->isFresh() && filled($existing->content)) {
            return [
                'ok' => true,
                'text' => (string) $existing->content,
                'from_cache' => true,
                'source' => (string) $existing->source,
            ];
        }

        $apiKey = (string) config('services.gemini.key');

        if ($apiKey === '') {
            $fallback = $this->localFallback($metrics);
            $this->storeInsight($key, $locale, $fallback, 'local_fallback', $metrics, $ttlHours);

            return [
                'ok' => true,
                'text' => $fallback,
                'from_cache' => false,
                'source' => 'local_fallback',
            ];
        }

        try {
            $text = $this->requestSummary($metrics, $apiKey);
            $this->storeInsight($key, $locale, $text, 'gemini', $metrics, $ttlHours);

            return [
                'ok' => true,
                'text' => $text,
                'from_cache' => false,
                'source' => 'gemini',
            ];
        } catch (\Throwable $e) {
            Log::warning('Gemini performance summary failed', [
                'message' => $e->getMessage(),
            ]);

            // Keep a still-fresh DB insight if present.
            if ($existing && $existing->isFresh() && filled($existing->content)) {
                return [
                    'ok' => true,
                    'text' => (string) $existing->content,
                    'from_cache' => true,
                    'source' => (string) $existing->source,
                ];
            }

            // Or keep expired content rather than nothing, while forcing a short local refresh window.
            if ($existing && filled($existing->content) && ! $forceRefresh) {
                return [
                    'ok' => true,
                    'text' => (string) $existing->content,
                    'from_cache' => true,
                    'source' => (string) $existing->source,
                ];
            }

            $fallback = $this->localFallback($metrics);
            $this->storeInsight($key, $locale, $fallback, 'local_fallback', $metrics, min(6, $ttlHours));

            return [
                'ok' => true,
                'text' => $fallback,
                'from_cache' => false,
                'source' => 'local_fallback',
            ];
        }
    }

    public function insightKey(string $locale = 'ar'): string
    {
        return 'performance_summary_ar';
    }

    protected function storeInsight(
        string $key,
        string $locale,
        string $content,
        string $source,
        array $metrics,
        int $ttlHours
    ): void {
        $snapshot = $metrics;
        unset($snapshot['generated_at']);

        Insight::query()->updateOrCreate(
            ['key' => $key],
            [
                'type' => 'performance_summary',
                'locale' => $locale,
                'content' => $content,
                'source' => $source,
                'metrics_snapshot' => $snapshot,
                'expires_at' => now()->addHours($ttlHours),
            ]
        );
    }

    protected function requestSummary(array $metrics, string $apiKey): string
    {
        $models = array_values(array_filter(array_unique([
            (string) config('services.gemini.model', 'gemini-flash-lite-latest'),
            'gemini-flash-lite-latest',
            'gemini-3.5-flash-lite',
            'gemini-3.1-flash-lite',
            'gemini-flash-latest',
            'gemini-2.5-flash',
        ])));

        $payloadJson = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
أنت MAQAM AI، محلل عمليات لوحة تحكم متجر.
اكتب ملخص أداء مفصل باللغة العربية فقط اعتماداً على بيانات JSON التالية فقط.

لازم تستخدم العناوين التالية حرفياً وفي سطر مستقل لكل عنوان:
1) نظرة عامة يومية
2) أداء آخر 7 أيام
3) نقاط تحتاج انتباه
4) توصيات سريعة

القواعد:
- النص عربي فقط (لا إنجليزي في العناوين أو الفقرات، إلا الأرقام ورموز العملة إن لزم).
- نص عادي فقط بدون markdown وبدون # وبدون * وبدون code blocks.
- كل قسم من 2 إلى 4 جمل كاملة. الطول الإجمالي تقريباً 180-280 كلمة.
- قارن أداء اليوم مع آخر 7 أيام عند توفر الأرقام.
- اذكر أرقاماً واضحة من JSON (الطلبات، المبيعات بالجنيه ج.م، المسح، التجار المعلّقين، المخزون المنخفض).
- كن عملياً وموجهاً للإجراء للمسؤول.
- لا تخترع أرقاماً غير موجودة في JSON.
- أسلوب مهني دافئ مثل مساعد ذكي.

Metrics JSON:
{$payloadJson}
PROMPT;

        $lastError = null;

        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::timeout(35)
                ->acceptJson()
                ->asJson()
                ->post($url.'?key='.urlencode($apiKey), [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.55,
                        'maxOutputTokens' => 1200,
                    ],
                ]);

            if ($response->successful()) {
                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                if (is_string($text) && trim($text) !== '') {
                    return trim($text);
                }

                $lastError = 'Gemini returned empty summary for '.$model;
                continue;
            }

            Log::warning('Gemini API error', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $lastError = 'Gemini API returned HTTP '.$response->status().' for '.$model;

            if (in_array($response->status(), [404, 429, 503], true)) {
                continue;
            }

            break;
        }

        throw new \RuntimeException($lastError ?: 'Gemini request failed');
    }

    protected function localFallback(array $metrics): string
    {
        $todayOrders = (int) data_get($metrics, 'today.orders', 0);
        $todayRevenue = (float) data_get($metrics, 'today.revenue', 0);
        $todayScans = (int) data_get($metrics, 'today.scans', 0);
        $weekOrders = (int) data_get($metrics, 'last_7_days.orders', 0);
        $weekRevenue = (float) data_get($metrics, 'last_7_days.revenue', 0);
        $weekScans = (int) data_get($metrics, 'last_7_days.scans', 0);
        $newOrders = (int) data_get($metrics, 'attention.new_orders', 0);
        $processing = (int) data_get($metrics, 'attention.processing_orders', 0);
        $pendingMerchants = (int) data_get($metrics, 'attention.pending_merchants', 0);
        $lowStock = (int) data_get($metrics, 'attention.low_stock_products', 0);

        return implode("\n\n", [
            "1) نظرة عامة يومية\nاليوم تم تسجيل {$todayOrders} طلب بإيرادات ".number_format($todayRevenue, 0).' ج.م، مع '.$todayScans.' عملية مسح. هذه قراءة مباشرة من بيانات المتجر الحالية.',
            "2) أداء آخر 7 أيام\nخلال الأسبوع سجل المتجر {$weekOrders} طلب و ".number_format($weekRevenue, 0)." ج.م مبيعات، و{$weekScans} عملية مسح. راقب الاتجاه اليومي مقارنة بمتوسط الأسبوع.",
            "3) نقاط تحتاج انتباه\nيوجد حالياً {$newOrders} طلب جديد و{$processing} قيد التحضير، مع {$pendingMerchants} تاجر بانتظار الموافقة و{$lowStock} منتج منخفض المخزون.",
            "4) توصيات سريعة\nابدأ بمعالجة الطلبات الجديدة والمخزون المنخفض، ثم راجع موافقات التجار حتى لا تتراكم المهام التشغيلية. تم إنشاء هذا الملخص محلياً لأن Gemini غير متاح مؤقتاً.",
        ]);
    }
}
