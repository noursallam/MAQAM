<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\WheelPrize;
use Illuminate\Database\Seeder;

class SeedWheelPrizesOnce extends Seeder
{
    public function run(): void
    {
        if (WheelPrize::count() > 0) {
            $this->command?->info('wheel_prizes already seeded');

            return;
        }

        WheelPrize::create([
            'type' => 'points',
            'label_ar' => '100 نقطة',
            'label_en' => '100 points',
            'weight' => 50,
            'points_amount' => 100,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        WheelPrize::create([
            'type' => 'discount',
            'label_ar' => 'خصم 15%',
            'label_en' => '15% off',
            'weight' => 25,
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        if ($c = Coupon::where('code', 'MAQAM10')->first()) {
            WheelPrize::create([
                'type' => 'coupon',
                'label_ar' => 'كوبون MAQAM10',
                'label_en' => 'MAQAM10 coupon',
                'weight' => 15,
                'coupon_id' => $c->id,
                'stock_limit' => 50,
                'is_active' => true,
                'sort_order' => 3,
            ]);
        }

        if ($p = Product::first()) {
            WheelPrize::create([
                'type' => 'product',
                'label_ar' => 'هدية: '.$p->name_ar,
                'label_en' => 'Gift: '.$p->name_en,
                'weight' => 10,
                'product_id' => $p->id,
                'stock_limit' => 20,
                'is_active' => true,
                'sort_order' => 4,
            ]);
        }

        $this->command?->info('Seeded '.WheelPrize::count().' wheel prizes');
    }
}
