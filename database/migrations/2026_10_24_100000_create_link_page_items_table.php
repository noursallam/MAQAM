<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The links page (/links): app downloads, social accounts and any other link, managed from the panel
        Schema::create('link_page_items', function (Blueprint $table) {
            $table->id();
            // android | ios | instagram | facebook | tiktok | whatsapp | youtube | x | snapchat | telegram | website | custom
            $table->string('platform', 30);
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->string('url', 1000);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });

        $whatsapp = preg_replace('/\D/', '', (string) (Schema::hasTable('system_settings')
            ? SystemSetting::where('key', 'store_whatsapp')->value('value')
            : ''));
        $now = now();

        // Starting points to edit; the store links stay hidden until a real address is filled in
        DB::table('link_page_items')->insert([
            ['platform' => 'android', 'label_ar' => null, 'label_en' => null, 'url' => 'https://play.google.com/store/apps/details?id=maqam.egypt', 'sort_order' => 1, 'is_active' => false],
            ['platform' => 'ios', 'label_ar' => null, 'label_en' => null, 'url' => 'https://apps.apple.com/', 'sort_order' => 2, 'is_active' => false],
            ['platform' => 'website', 'label_ar' => 'تسوّق من متجرنا', 'label_en' => 'Shop our store', 'url' => rtrim((string) config('app.url'), '/'), 'sort_order' => 3, 'is_active' => true],
            ['platform' => 'whatsapp', 'label_ar' => 'كلّمنا على واتساب', 'label_en' => 'Chat on WhatsApp', 'url' => $whatsapp ? "https://wa.me/{$whatsapp}" : 'https://wa.me/', 'sort_order' => 4, 'is_active' => $whatsapp !== ''],
            ['platform' => 'facebook', 'label_ar' => null, 'label_en' => null, 'url' => 'https://facebook.com/', 'sort_order' => 5, 'is_active' => false],
            ['platform' => 'instagram', 'label_ar' => null, 'label_en' => null, 'url' => 'https://instagram.com/', 'sort_order' => 6, 'is_active' => false],
            ['platform' => 'tiktok', 'label_ar' => null, 'label_en' => null, 'url' => 'https://tiktok.com/', 'sort_order' => 7, 'is_active' => false],
        ]);
        DB::table('link_page_items')->update(['created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('link_page_items');
    }
};
