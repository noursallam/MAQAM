<?php

use App\Models\Rank;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any demo ranks that the old seeder created.
        Rank::query()->delete();

        $ranks = [
            ['name_en' => 'Starter',   'name_ar' => 'مبتدئ',    'min_points' => 0,     'max_points' => 199,   'icon_url' => '/badges/starter.png'],
            ['name_en' => 'Bronze',    'name_ar' => 'برونزي',   'min_points' => 200,   'max_points' => 599,   'icon_url' => '/badges/bronze.png'],
            ['name_en' => 'Silver',    'name_ar' => 'فضي',     'min_points' => 600,   'max_points' => 1199,  'icon_url' => '/badges/silver.png'],
            ['name_en' => 'Gold',      'name_ar' => 'ذهبي',    'min_points' => 1200,  'max_points' => 2499,  'icon_url' => '/badges/gold.png'],
            ['name_en' => 'Platinum',  'name_ar' => 'بلاتيني',  'min_points' => 2500,  'max_points' => 4999,  'icon_url' => '/badges/platinum.png'],
            ['name_en' => 'Diamond',   'name_ar' => 'ماسي',    'min_points' => 5000,  'max_points' => 9999,  'icon_url' => '/badges/diamond.png'],
            ['name_en' => 'Elite',     'name_ar' => 'نخبة',    'min_points' => 10000, 'max_points' => 14999, 'icon_url' => '/badges/elite.png'],
            ['name_en' => 'Royal',     'name_ar' => 'ملكي',    'min_points' => 15000, 'max_points' => null,  'icon_url' => '/badges/royal.png'],
        ];

        foreach ($ranks as $rank) {
            Rank::create(array_merge($rank, [
                'customer_points_per_scan' => 10,
                'merchant_points_per_scan' => 5,
                'wheel_win_probability' => 0.25,
                'wheel_cost_points' => 50,
                'is_active' => true,
            ]));
        }
    }

    public function down(): void
    {
        Rank::query()->whereIn('name_en', [
            'Starter', 'Bronze', 'Silver', 'Gold',
            'Platinum', 'Diamond', 'Elite', 'Royal',
        ])->delete();
    }
};
