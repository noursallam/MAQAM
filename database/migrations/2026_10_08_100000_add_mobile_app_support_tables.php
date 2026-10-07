<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deleting a saved address must not erase it from the orders shipped to it
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->softDeletes();
        });

        // One push token per installed app, so a user's phone and tablet both receive
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('users')->whereNotNull('device_token')->where('device_token', '!=', '')->orderBy('id')
            ->each(function ($user) use ($now) {
                DB::table('device_tokens')->insertOrIgnore([
                    'user_id' => $user->id,
                    'token' => $user->device_token,
                    'last_used_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        // Editable from Admin > Settings; read by GET /api/v1/app/config
        foreach ([
            ['app_min_version_android', '1.0.0', 'أقل إصدار مسموح لتطبيق أندرويد (الأقدم يُجبر على التحديث)'],
            ['app_min_version_ios', '1.0.0', 'أقل إصدار مسموح لتطبيق iOS (الأقدم يُجبر على التحديث)'],
            ['app_latest_version_android', '1.0.0', 'أحدث إصدار متاح لتطبيق أندرويد'],
            ['app_latest_version_ios', '1.0.0', 'أحدث إصدار متاح لتطبيق iOS'],
            ['app_store_url_android', '', 'رابط التطبيق على Google Play'],
            ['app_store_url_ios', '', 'رابط التطبيق على App Store'],
        ] as [$key, $value, $description]) {
            DB::table('system_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'group' => 'app',
                'description' => $description,
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->where('group', 'app')->delete();
        Schema::dropIfExists('device_tokens');
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
