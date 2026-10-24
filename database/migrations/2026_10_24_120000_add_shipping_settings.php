<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Shipping was only seeded locally, so production fell back to the hardcoded defaults with nothing to edit
        foreach ([
            ['shipping_cost', '35.00', 'تكلفة الشحن الثابتة للطلبات'],
            ['free_shipping_threshold', '500.00', 'الحد الأدنى للشحن المجاني'],
        ] as [$key, $value, $description]) {
            DB::table('system_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'group' => 'store',
                'description' => $description,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['shipping_cost', 'free_shipping_threshold'])->delete();
    }
};
