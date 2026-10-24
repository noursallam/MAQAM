<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Wording of the mobile app changed from the dashboard. The app keeps its own text for every key
 * and only uses a value from here when it is filled in.
 */
class AppText extends Model
{
    public const CACHE_KEY = 'app_texts.payload';

    protected $fillable = ['key', 'value_ar', 'value_en'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * The app's built-in wording, exported from its translation files by `php artisan app-texts:sync`.
     *
     * @return array<string, array{ar: string, en: string, placeholders: list<string>, plural: bool}>
     */
    public static function defaults(): array
    {
        $path = resource_path('app_texts/defaults.json');

        return is_file($path) ? json_decode((string) file_get_contents($path), true) ?: [] : [];
    }

    /** Sections of the dashboard page, by the first camelCase word of a key. */
    private const GROUPS = [
        'account' => 'account', 'address' => 'address', 'addresses' => 'address', 'assistant' => 'assistant',
        'cart' => 'cart', 'checkout' => 'checkout', 'payment' => 'checkout', 'summary' => 'checkout',
        'delete' => 'account', 'suspended' => 'account', 'home' => 'home', 'nav' => 'home',
        'login' => 'login', 'otp' => 'login', 'send' => 'login', 'password' => 'login', 'setup' => 'login', 'sign' => 'login',
        'biometric' => 'security', 'lock' => 'security', 'pin' => 'security', 'unlock' => 'security',
        'merchant' => 'merchant', 'notification' => 'notifications', 'notifications' => 'notifications',
        'onboarding' => 'onboarding', 'order' => 'orders', 'orders' => 'orders',
        'product' => 'products', 'catalog' => 'products', 'sort' => 'products', 'filter' => 'products', 'price' => 'products', 'quantity' => 'products', 'store' => 'products',
        'reward' => 'rewards', 'rewards' => 'rewards', 'wheel' => 'rewards', 'wallet' => 'rewards', 'points' => 'rewards',
        'scan' => 'scan', 'pending' => 'scan', 'seminar' => 'seminars', 'update' => 'general',
    ];

    /**
     * The section of the app a key belongs to ("merchantShare" → "merchant"); small leftovers share "general".
     */
    public static function groupOf(string $key): string
    {
        return self::GROUPS[preg_match('/^[a-z]+/', $key, $m) ? $m[0] : ''] ?? 'general';
    }

    /**
     * What the app downloads: only the filled-in overrides, per language.
     *
     * @return array{version: int, texts: array{ar: array<string, string>, en: array<string, string>}}
     */
    public static function payload(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $rows = self::all();
            $texts = ['ar' => [], 'en' => []];

            foreach ($rows as $row) {
                foreach (['ar', 'en'] as $locale) {
                    $value = trim((string) $row->{'value_'.$locale});
                    if ($value !== '') {
                        $texts[$locale][$row->key] = $value;
                    }
                }
            }

            return [
                'version' => (int) $rows->max('updated_at')?->timestamp,
                'texts' => [
                    'ar' => (object) $texts['ar'],
                    'en' => (object) $texts['en'],
                ],
            ];
        });
    }
}
