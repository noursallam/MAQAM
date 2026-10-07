<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(90)->by($request->user()?->id ?: $request->ip()));

        // Starting a WhatsApp verification is limited per IP and per phone number
        RateLimiter::for('otp-start', fn (Request $request) => [
            Limit::perMinute(5)->by('otp-ip:'.$request->ip()),
            Limit::perMinutes(15, 5)->by('otp-phone:'.preg_replace('/\D/', '', (string) $request->input('phone'))),
        ]);
        RateLimiter::for('otp-poll', fn (Request $request) => Limit::perMinute(40)->by($request->ip()));
        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        \Illuminate\Support\Facades\View::composer('store.*', function ($view) {
            $cartCount = 0;
            try {
                $cartService = app(\App\Services\Store\CartService::class);
                $cartCount = $cartService->count();
            } catch (\Throwable $e) {
                // In console/migrations fallback
            }

            $storeSettings = [
                'phone' => \App\Models\SystemSetting::getValue('store_phone', '01001234567'),
                'whatsapp' => \App\Models\SystemSetting::getValue('store_whatsapp', '201001234567'),
                'email' => \App\Models\SystemSetting::getValue('support_email', 'support@maqam-eg.com'),
                'announcement_ar' => \App\Models\SystemSetting::getValue('announcement_text_ar', 'أدوات كهربائية موثوقة للتركيب والاستخدام مع نقاط ولاء عبر مسح QR'),
                'announcement_en' => \App\Models\SystemSetting::getValue('announcement_text_en', 'Reliable electrical supplies with loyalty points rewards on every purchase'),
            ];

            $view->with('cartCount', $cartCount);
            $view->with('storeSettings', $storeSettings);
        });
    }
}
