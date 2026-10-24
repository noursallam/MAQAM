<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Every setting the code reads, so a fresh server lists them all in the dashboard without running a seeder.
        // Values are the ones the code already falls back to; existing rows are left alone.
        foreach ([
            ['wheel_enabled', '1', 'wheel', 'تفعيل عجلة الحظ', true],
            ['wheel_discount_expiry_days', '30', 'wheel', 'عدد أيام صلاحية خصم العجلة', false],
            ['low_stock_threshold', '50', 'inventory', 'حد تنبيه المخزون المنخفض', false],
            ['support_email', 'support@maqam-eg.com', 'general', 'بريد الدعم', true],
            ['default_language', 'ar', 'general', 'اللغة الافتراضية', true],
            ['store_phone', '01001234567', 'store', 'رقم هاتف خدمة العملاء', true],
            ['store_whatsapp', '201001234567', 'store', 'رقم واتساب المتجر', true],
            ['store_address', 'القاهرة، جمهورية مصر العربية', 'store', 'عنوان المقر الرئيسي للمتجر', true],
            ['announcement_text_ar', 'أدوات كهربائية موثوقة للتركيب والاستخدام مع نقاط ولاء عبر مسح QR', 'store', 'شريط الإعلانات أعلى الموقع', true],
            ['announcement_text_en', 'Reliable electrical supplies with loyalty points rewards on every purchase', 'store', 'Top announcement bar English', true],
        ] as [$key, $value, $group, $description, $isPublic]) {
            DB::table('system_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Settings may have been edited since; dropping them would lose that
    }
};
