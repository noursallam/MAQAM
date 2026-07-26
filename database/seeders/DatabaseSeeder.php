<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CategoryPrize;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\Rank;
use App\Models\ShippingAddress;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $silver = Rank::create([
            'name_en' => 'Silver', 'name_ar' => 'فضي',
            'min_points' => 0, 'max_points' => 999,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 5,
            'wheel_win_probability' => 0.20, 'wheel_cost_points' => 50, 'is_active' => true,
        ]);
        $gold = Rank::create([
            'name_en' => 'Gold', 'name_ar' => 'ذهبي',
            'min_points' => 1000, 'max_points' => 4999,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 8,
            'wheel_win_probability' => 0.35, 'wheel_cost_points' => 50, 'is_active' => true,
        ]);
        $platinum = Rank::create([
            'name_en' => 'Platinum', 'name_ar' => 'بلاتيني',
            'min_points' => 5000, 'max_points' => null,
            'customer_points_per_scan' => 10, 'merchant_points_per_scan' => 12,
            'wheel_win_probability' => 0.50, 'wheel_cost_points' => 50, 'is_active' => true,
        ]);

        $adminUser = User::create([
            'phone_number' => '01000000000',
            'email' => 'admin@maqam-eg.com',
            'password' => Hash::make('password'),
            'full_name' => 'MAQAM Super Admin',
            'role' => 'admin',
            'is_active' => true,
            'preferred_language' => 'ar',
        ]);

        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'role' => 'super_admin',
            'permissions' => ['*'],
            'last_activity_at' => now(),
        ]);

        $customers = collect();
        foreach ([
            ['نور سلام', '01111111111', 'customer@maqam-eg.com', $gold->id, 1450, true],
            ['سارة أحمد', '01122222222', 'sara@maqam-eg.com', $silver->id, 320, true],
            ['كريم حسن', '01133333333', 'karim@maqam-eg.com', $platinum->id, 6200, true],
            ['حساب مخاطر', '01144444444', 'risk@maqam-eg.com', $silver->id, 80, false],
        ] as [$name, $phone, $email, $rankId, $pts, $active]) {
            $u = User::create([
                'phone_number' => $phone,
                'email' => $email,
                'password' => Hash::make('password'),
                'full_name' => $name,
                'role' => 'customer',
                'is_active' => $active,
                'preferred_language' => 'ar',
                'last_login_at' => now()->subDays(rand(0, 20)),
            ]);
            $customers->push(Customer::create([
                'user_id' => $u->id,
                'rank_id' => $rankId,
                'points_balance' => $pts,
                'total_points_earned' => $pts + 200,
                'total_points_spent' => 200,
            ]));
        }

        // Approved merchants
        $approvedMerchants = collect();
        foreach ([
            ['عطور النيل', 'شارع الهرم، الجيزة', 'M-NILE01'],
            ['بوتيك الفخامة', 'مدينة نصر، القاهرة', 'M-LUXE02'],
        ] as [$biz, $addr, $code]) {
            $u = User::create([
                'phone_number' => '012'.rand(10000000, 99999999),
                'email' => Str::slug($biz).'@maqam-eg.com',
                'password' => Hash::make('password'),
                'full_name' => 'مالك '.$biz,
                'role' => 'merchant',
                'is_active' => true,
            ]);
            $approvedMerchants->push(Merchant::create([
                'user_id' => $u->id,
                'business_name' => $biz,
                'business_address' => $addr,
                'merchant_code' => $code,
                'is_approved' => true,
                'approved_at' => now()->subDays(3),
                'approved_by' => $admin->id,
            ]));
        }

        // Pending merchants for inbox
        foreach ([
            ['متجر الورود', 'الإسكندرية', 'M-PEND01'],
            ['دار العود', 'المنصورة', 'M-PEND02'],
            ['MAQAM Boutique', 'أسيوط', 'M-PEND03'],
        ] as [$biz, $addr, $code]) {
            $u = User::create([
                'phone_number' => '015'.rand(10000000, 99999999),
                'email' => Str::slug($biz).rand(10, 99).'@maqam-eg.com',
                'password' => Hash::make('password'),
                'full_name' => 'طالب '.$biz,
                'role' => 'merchant',
                'is_active' => true,
            ]);
            Merchant::create([
                'user_id' => $u->id,
                'business_name' => $biz,
                'business_address' => $addr,
                'merchant_code' => $code,
                'is_approved' => false,
            ]);
        }

        CategoryPrize::insert([
            [
                'name_en' => 'Prize Category 1', 'name_ar' => 'فئة هدايا 1',
                'category_type' => 'gift', 'points_value' => 10,
                'background_color' => '#22C55E', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name_en' => 'Prize Category 2', 'name_ar' => 'فئة هدايا 2',
                'category_type' => 'gift', 'points_value' => 25,
                'background_color' => '#3B82F6', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name_en' => 'Prize Category 3', 'name_ar' => 'فئة هدايا 3',
                'category_type' => 'gift', 'points_value' => 50,
                'background_color' => '#C5A059', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $prize1 = CategoryPrize::where('background_color', '#22C55E')->first();
        $batchId = 'BATCH-DEMO-GREEN';
        for ($i = 0; $i < 12; $i++) {
            QrCode::create([
                'serial_code' => str_pad((string) (2200000000000000 + $i), 16, '0', STR_PAD_LEFT),
                'category_id' => $prize1->id,
                'points_awarded' => 10,
                'status' => $i < 4 ? 'used' : 'active',
                'generated_at' => now()->subDay(),
                'batch_id' => $batchId,
                'used_at' => $i < 4 ? now()->subHours($i + 1) : null,
                'used_by_customer_id' => $i < 4 ? $customers[$i % $customers->count()]->id : null,
            ]);
        }

        $storeCat = Category::create([
            'name_en' => 'Fragrances', 'name_ar' => 'عطور',
            'slug' => 'fragrances', 'is_active' => true,
        ]);
        Category::create([
            'name_en' => 'Gift Sets', 'name_ar' => 'مجموعات هدايا',
            'slug' => 'gift-sets', 'is_active' => true,
        ]);

        $products = collect([
            ['MAQAM Signature', 'MAQAM Signature', 'MQM-SIG-001', 450, 120],
            ['MAQAM Noir', 'MAQAM Noir', 'MQM-NOIR-002', 520, 35],
            ['MAQAM Oud', 'MAQAM Oud', 'MQM-OUD-003', 680, 8],
            ['MAQAM Mist', 'MAQAM Mist', 'MQM-MST-004', 220, 0],
            ['MAQAM Travel', 'MAQAM Travel', 'MQM-TRV-005', 180, 55],
        ])->map(fn ($p) => Product::create([
            'category_id' => $storeCat->id,
            'name_en' => $p[0],
            'name_ar' => $p[1],
            'description_en' => 'Premium fragrance',
            'description_ar' => 'عطر فاخر',
            'price' => $p[3],
            'stock_quantity' => $p[4],
            'sku' => $p[2],
            'is_active' => true,
        ]));

        $mainCustomer = $customers->first();
        $address = ShippingAddress::create([
            'user_id' => $mainCustomer->user_id,
            'address_line1' => 'شارع التحرير 12',
            'city' => 'القاهرة',
            'governorate' => 'القاهرة',
            'country' => 'Egypt',
            'phone' => '01111111111',
            'recipient_name' => $mainCustomer->user->full_name,
            'is_default' => true,
        ]);

        $statuses = ['new', 'new', 'processing', 'shipped', 'delivered', 'cancelled'];
        foreach ($statuses as $i => $status) {
            $order = Order::create([
                'user_id' => $customers[$i % $customers->count()]->user_id,
                'order_number' => 'MQM-'.str_pad((string) (1000 + $i), 5, '0', STR_PAD_LEFT),
                'status' => $status,
                'subtotal' => 450,
                'tax' => 0,
                'discount' => 0,
                'shipping_cost' => 40,
                'total_amount' => 490,
                'payment_method' => ['cod', 'paymob', 'wallet'][$i % 3],
                'payment_status' => $status === 'cancelled' ? 'failed' : ($status === 'delivered' ? 'paid' : 'pending'),
                'shipping_address_id' => $address->id,
                'shipped_at' => in_array($status, ['shipped', 'delivered'], true) ? now()->subDay() : null,
                'delivered_at' => $status === 'delivered' ? now() : null,
                'cancelled_at' => $status === 'cancelled' ? now() : null,
                'cancellation_reason' => $status === 'cancelled' ? 'طلب العميل' : null,
                'created_at' => now()->subDays(6 - $i),
            ]);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $products[$i % $products->count()]->id,
                'quantity' => 1,
                'unit_price' => 450,
                'subtotal' => 450,
            ]);
        }

        $usedCodes = QrCode::where('status', 'used')->get();
        foreach ($usedCodes as $i => $code) {
            $customer = $customers[$i % $customers->count()];
            $merchant = $approvedMerchants[$i % $approvedMerchants->count()];
            QrScan::create([
                'qr_code_id' => $code->id,
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'points_awarded_customer' => $code->points_awarded,
                'points_awarded_merchant' => 5,
                'scan_location_lat' => (string) (30.0 + $i * 0.8),
                'scan_location_lng' => (string) (31.2 + $i * 0.5),
                'scanned_at' => now()->subHours($i + 1),
                'is_offline' => $i === 2,
                'sync_status' => $i === 2 ? 'failed' : 'synced',
                'device_id' => 'demo-device-'.$i,
            ]);

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'type' => 'earn',
                'amount' => $code->points_awarded,
                'description' => 'مسح QR',
                'balance_after' => $customer->points_balance,
                'transaction_date' => now()->subHours($i + 1),
            ]);
        }

        // Extra geo-velocity scans for risk desk
        $riskCustomer = $customers->first(fn ($c) => $c->user->full_name === 'حساب مخاطر') ?? $customers->last();
        QrScan::create([
            'qr_code_id' => QrCode::where('status', 'active')->first()->id,
            'customer_id' => $riskCustomer->id,
            'merchant_id' => $approvedMerchants->first()->id,
            'points_awarded_customer' => 10,
            'points_awarded_merchant' => 5,
            'scan_location_lat' => '30.0444',
            'scan_location_lng' => '31.2357',
            'scanned_at' => now()->subMinutes(10),
            'is_offline' => false,
            'sync_status' => 'synced',
            'device_id' => 'risk-1',
        ]);
        QrScan::create([
            'qr_code_id' => QrCode::where('status', 'active')->skip(1)->first()->id,
            'customer_id' => $riskCustomer->id,
            'merchant_id' => $approvedMerchants->last()->id,
            'points_awarded_customer' => 10,
            'points_awarded_merchant' => 5,
            'scan_location_lat' => '31.2001',
            'scan_location_lng' => '29.9187',
            'scanned_at' => now()->subMinutes(5),
            'is_offline' => false,
            'sync_status' => 'synced',
            'device_id' => 'risk-1',
        ]);

        Coupon::create([
            'code' => 'MAQAM10',
            'type' => 'percentage',
            'value' => 10,
            'scope' => 'all',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addMonth(),
            'usage_limit' => 100,
            'used_count' => 4,
            'min_order_amount' => 100,
            'is_active' => true,
        ]);

        $demoCoupon = Coupon::where('code', 'MAQAM10')->first();
        $demoProduct = Product::query()->first();

        $pointsPrize = WheelPrize::create([
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
        if ($demoCoupon) {
            WheelPrize::create([
                'type' => 'coupon',
                'label_ar' => 'كوبون MAQAM10',
                'label_en' => 'MAQAM10 coupon',
                'weight' => 15,
                'coupon_id' => $demoCoupon->id,
                'stock_limit' => 50,
                'is_active' => true,
                'sort_order' => 3,
            ]);
        }
        if ($demoProduct) {
            WheelPrize::create([
                'type' => 'product',
                'label_ar' => 'هدية: '.$demoProduct->name_ar,
                'label_en' => 'Gift: '.$demoProduct->name_en,
                'weight' => 10,
                'product_id' => $demoProduct->id,
                'stock_limit' => 20,
                'is_active' => true,
                'sort_order' => 4,
            ]);
        }

        WheelSpin::create([
            'customer_id' => $mainCustomer->id,
            'rank_id' => $gold->id,
            'wheel_prize_id' => $pointsPrize->id,
            'points_cost' => 50,
            'points_won' => 100,
            'prize_type' => 'points',
            'prize_value' => '100',
            'is_win' => true,
            'probability_used' => 0.35,
            'spun_at' => now()->subHours(2),
        ]);
        WheelSpin::create([
            'customer_id' => $customers[1]->id,
            'rank_id' => $silver->id,
            'points_cost' => 50,
            'points_won' => 0,
            'prize_type' => 'none',
            'prize_value' => null,
            'is_win' => false,
            'probability_used' => 0.20,
            'spun_at' => now()->subHours(5),
        ]);

        $settings = [
            ['key' => 'wheel_enabled', 'value' => '1', 'group' => 'wheel', 'description' => 'تفعيل عجلة الحظ', 'is_public' => true],
            ['key' => 'low_stock_threshold', 'value' => '50', 'group' => 'inventory', 'description' => 'حد تنبيه المخزون المنخفض', 'is_public' => false],
            ['key' => 'support_email', 'value' => 'support@maqam-eg.com', 'group' => 'general', 'description' => 'بريد الدعم', 'is_public' => true],
            ['key' => 'default_language', 'value' => 'ar', 'group' => 'general', 'description' => 'اللغة الافتراضية', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
