<?php

namespace App\Providers;

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
