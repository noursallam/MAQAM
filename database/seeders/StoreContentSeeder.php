<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure core electrical categories exist
        $categoriesData = [
            [
                'name_en' => 'Switches & Sockets',
                'name_ar' => 'مفاتيح وبرايز',
                'slug' => 'switches-sockets',
                'image_path' => 'store/img/categories/switches-sockets.jpg',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="7" y="3" width="10" height="14" rx="2"/><path d="M10 7v4M14 7v4M9 21h6"/></svg>',
            ],
            [
                'name_en' => 'Breakers & Panels',
                'name_ar' => 'قواطع ولوحات توزيع',
                'slug' => 'breakers',
                'image_path' => 'store/img/categories/breakers.jpg',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>',
            ],
            [
                'name_en' => 'Cables & Wires',
                'name_ar' => 'كابلات وأسلاك',
                'slug' => 'cables',
                'image_path' => 'store/img/categories/cables.jpg',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8c4 0 4 8 8 8s4-8 8-8"/><path d="M4 16c4 0 4-8 8-8s4 8 8 8"/></svg>',
            ],
            [
                'name_en' => 'Lighting & LED',
                'name_ar' => 'إضاءة وليد',
                'slug' => 'lighting',
                'image_path' => 'store/img/categories/lighting.svg',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18h6M10 21h4"/><path d="M8 10a4 4 0 1 1 8 0c0 2-1.5 3-2 4H10c-.5-1-2-2-2-4z"/></svg>',
            ],
            [
                'name_en' => 'Electrical Tools & Accessories',
                'name_ar' => 'أدوات وإكسسوارات كهربائية',
                'slug' => 'tools',
                'image_path' => 'store/img/categories/tools.svg',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $cat = Category::where('slug', $c['slug'])
                ->orWhere('name_en', $c['name_en'])
                ->orWhere('name_ar', $c['name_ar'])
                ->first();

            if (! $cat) {
                $cat = Category::create([
                    'name_en' => $c['name_en'],
                    'name_ar' => $c['name_ar'],
                    'slug' => $c['slug'],
                    'image_path' => $c['image_path'],
                    'icon' => $c['icon'],
                    'is_active' => true,
                ]);
            } else {
                $cat->update([
                    'slug' => $c['slug'],
                    'icon' => $c['icon'],
                    'image_path' => $c['image_path'],
                    'is_active' => true,
                ]);
            }

            $categories[$c['slug']] = $cat;
        }

        $switchesCat = $categories['switches-sockets'] ?? Category::first();
        $breakersCat = $categories['breakers'];
        $cablesCat = $categories['cables'];
        $lightingCat = $categories['lighting'];
        $toolsCat = $categories['tools'];

        // 2. Update existing 113 products with realistic pricing and stock
        $products = Product::all();
        foreach ($products as $p) {
            $name = $p->name_ar . ' ' . $p->name_en;
            $price = 45.00;
            $stock = 60;
            $catId = $switchesCat->id;

            if (str_contains($name, 'تكييف') || str_contains($name, '45')) {
                $price = 185.00;
                $stock = 35;
            } elseif (str_contains($name, 'سخان') || str_contains($name, '20')) {
                $price = 115.00;
                $stock = 45;
            } elseif (str_contains($name, 'ديفاتير') || str_contains($name, '2way')) {
                $price = 55.00;
                $stock = 70;
            } elseif (str_contains($name, 'مجوز') || str_contains($name, '2gang')) {
                $price = 65.00;
                $stock = 60;
            } elseif (str_contains($name, 'ديجاتوري') || str_contains($name, 'intermediate')) {
                $price = 78.00;
                $stock = 30;
            } elseif (str_contains($name, 'جرس') || str_contains($name, 'bell')) {
                $price = 42.00;
                $stock = 50;
            } elseif (str_contains($name, 'بريزة') || str_contains($name, 'socket')) {
                $price = str_contains($name, 'مجوفة') ? 85.00 : 48.00;
                $stock = 80;
            } elseif (str_contains($name, 'قاطع') || str_contains($name, 'breaker')) {
                $price = 145.00;
                $stock = 40;
                $catId = $breakersCat->id;
            } elseif (str_contains($name, 'كابل') || str_contains($name, 'سلك') || str_contains($name, 'cable')) {
                $price = 280.00;
                $stock = 25;
                $catId = $cablesCat->id;
            } else {
                $price = 38.00;
                $stock = 75;
            }

            // Adjust by color finish
            if (str_contains(strtolower($name), 'champagne') || str_contains($name, 'بيج')) {
                $price += 5;
            } elseif (str_contains(strtolower($name), 'grey') || str_contains($name, 'رصاصى') || str_contains(strtolower($name), 'black') || str_contains($name, 'اسمر')) {
                $price += 3;
            }

            $p->update([
                'category_id' => $catId,
                'price' => $price,
                'stock_quantity' => $stock,
                'is_active' => true,
            ]);
        }

        // 3. Add representative products for Breakers, Cables, Lighting, Tools if empty
        if ($breakersCat->products()->count() < 3) {
            Product::firstOrCreate(
                ['sku' => 'MQM-BRK-1P16A'],
                [
                    'category_id' => $breakersCat->id,
                    'name_ar' => 'قاطع أحادي 16 أمبير 4.5 كيلو أمبير',
                    'name_en' => 'MCB Single Pole 16A 4.5kA',
                    'description_ar' => 'قاطع تيار أوتوماتيكي أحادي القطب لحماية الدوائر الكهربائية والإنارة',
                    'description_en' => 'Single pole miniature circuit breaker for electrical protection',
                    'price' => 125.00,
                    'stock_quantity' => 50,
                    'is_active' => true,
                ]
            );
            Product::firstOrCreate(
                ['sku' => 'MQM-BRK-2P32A'],
                [
                    'category_id' => $breakersCat->id,
                    'name_ar' => 'قاطع ثنائي 32 أمبير 6 كيلو أمبير',
                    'name_en' => 'MCB Double Pole 32A 6kA',
                    'description_ar' => 'قاطع تيار أوتوماتيكي ثنائي لحماية المكيفات والأجهزة الكبيرة',
                    'description_en' => 'Double pole miniature circuit breaker for air conditioning protection',
                    'price' => 240.00,
                    'stock_quantity' => 35,
                    'is_active' => true,
                ]
            );
        }

        if ($cablesCat->products()->count() < 3) {
            Product::firstOrCreate(
                ['sku' => 'MQM-CBL-1X2.5MM'],
                [
                    'category_id' => $cablesCat->id,
                    'name_ar' => 'لفة سلك نحاس معزول 2.5 مم² 100 متر',
                    'name_en' => 'Single Core Copper Wire 2.5mm² 100m',
                    'description_ar' => 'سلك نحاس نقي معزول PVC عالي الجودة للتركيبات الداخلية وبرايز القوى',
                    'description_en' => 'High quality pure copper wire 100m roll for socket circuits',
                    'price' => 450.00,
                    'stock_quantity' => 30,
                    'is_active' => true,
                ]
            );
            Product::firstOrCreate(
                ['sku' => 'MQM-CBL-1X4.0MM'],
                [
                    'category_id' => $cablesCat->id,
                    'name_ar' => 'لفة سلك نحاس معزول 4.0 مم² 100 متر',
                    'name_en' => 'Single Core Copper Wire 4.0mm² 100m',
                    'description_ar' => 'سلك نحاس نقي معزول PVC لتحمل أحمال السخانات والمكيفات',
                    'description_en' => 'Heavy duty pure copper wire 100m roll for high load circuits',
                    'price' => 690.00,
                    'stock_quantity' => 20,
                    'is_active' => true,
                ]
            );
        }

        if ($lightingCat->products()->count() < 3) {
            Product::firstOrCreate(
                ['sku' => 'MQM-LED-PANEL-18W'],
                [
                    'category_id' => $lightingCat->id,
                    'name_ar' => 'بانل ليد غاطس 18 وات إضاءة ورم/أبيض',
                    'name_en' => 'Recessed LED Panel 18W Warm/White',
                    'description_ar' => 'لوحة إضاءة ليد نحيفة موفرة للطاقة مناسبة للأسقف المعلقة والجبس',
                    'description_en' => 'Slim recessed LED panel 18W energy saving',
                    'price' => 95.00,
                    'stock_quantity' => 65,
                    'is_active' => true,
                ]
            );
            Product::firstOrCreate(
                ['sku' => 'MQM-LED-SPOT-7W'],
                [
                    'category_id' => $lightingCat->id,
                    'name_ar' => 'سبوت لايت ليد 7 وات متحرك مع الشاسيه',
                    'name_en' => 'Adjustable LED Spotlight 7W with Frame',
                    'description_ar' => 'سبوت ليد فاخر ذو توجيه متحرك لإنارة ديكورية مركزة',
                    'description_en' => 'Adjustable architectural LED spotlight 7W',
                    'price' => 65.00,
                    'stock_quantity' => 80,
                    'is_active' => true,
                ]
            );
        }

        if ($toolsCat->products()->count() < 3) {
            Product::firstOrCreate(
                ['sku' => 'MQM-TOOL-TESTER'],
                [
                    'category_id' => $toolsCat->id,
                    'name_ar' => 'مفك اختبار كهربائي رقمي بشاشة LCD',
                    'name_en' => 'Digital Voltage Tester Screwdriver with LCD',
                    'description_ar' => 'مفك فاحص للجهد الكهربائي والتيار والفيز مع قراءة رقمية واضحة',
                    'description_en' => 'Digital voltage and continuity tester screwdriver',
                    'price' => 75.00,
                    'stock_quantity' => 45,
                    'is_active' => true,
                ]
            );
            Product::firstOrCreate(
                ['sku' => 'MQM-TOOL-TAPE-SET'],
                [
                    'category_id' => $toolsCat->id,
                    'name_ar' => 'مجموعة شريط عازل كهربائي (شيكرتون) 10 رول',
                    'name_en' => 'Electrical Insulation PVC Tape Pack (10 Rolls)',
                    'description_ar' => 'شريط لاصق عازل للكهرباء مقاوم للحرارة والرطوبة بألوان متعددة',
                    'description_en' => 'Multi-colored PVC electrical insulation tape 10 rolls',
                    'price' => 55.00,
                    'stock_quantity' => 90,
                    'is_active' => true,
                ]
            );
        }

        // 4. Populate System Settings for store and checkout
        $settings = [
            ['shipping_cost', '35.00', 'store', 'تكلفة الشحن الثابتة للطلبات', true],
            ['free_shipping_threshold', '500.00', 'store', 'الحد الأدنى للشحن المجاني', true],
            ['store_phone', '01001234567', 'store', 'رقم هاتف خدمة العملاء', true],
            ['store_whatsapp', '201001234567', 'store', 'رقم واتساب المتجر', true],
            ['support_email', 'support@maqam-eg.com', 'store', 'بريد الدعم والمبيعات', true],
            ['store_address', 'القاهرة، جمهورية مصر العربية', 'store', 'عنوان المقر الرئيسي للمتجر', true],
            ['announcement_text_ar', 'أدوات كهربائية موثوقة للتركيب والاستخدام مع نقاط ولاء عبر مسح QR', 'store', 'شريط الإعلانات أعلى الموقع', true],
            ['announcement_text_en', 'Reliable electrical supplies with loyalty points rewards on every purchase', 'store', 'Top announcement bar English', true],
        ];

        foreach ($settings as [$key, $value, $group, $desc, $isPublic]) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $group,
                    'description' => $desc,
                    'is_public' => $isPublic,
                ]
            );
        }

        // 5. Initial storefront Banners
        Banner::updateOrCreate(
            ['slot' => 'home_hero_1'],
            [
                'title_ar' => 'أدوات ومستلزمات كهربائية بمعايير جودة فائقة',
                'title_en' => 'Premium Electrical Supplies & Modular Switches',
                'image_path' => 'store/img/hero-switches.jpg',
                'link_url' => '/shop',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
        Banner::updateOrCreate(
            ['slot' => 'home_hero_2'],
            [
                'title_ar' => 'امسح كود QR واربح نقاط وهدايا مع كل قطعة',
                'title_en' => 'Scan QR Codes & Win Rewards on Every Purchase',
                'image_path' => 'identity/56829c8b-2436-44e5-9110-95abb1027fea.png',
                'link_url' => '/loyalty',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}
