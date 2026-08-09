<?php

/**
 * Reset store catalog to MAQAM switches/sockets only, then import.
 *
 * - Deletes ALL store categories + products (seed fragrances/gift-sets/etc.)
 * - Does NOT touch admins, users, QR, prizes, ranks, merchants, …
 * - sku = unique 16-digit barcode
 * - catalog_code / production_code / system_code stored as options
 *
 * Run:
 *   php artisan tinker --execute="require base_path('database/scripts/import_maqam_catalog.php');"
 */

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSkuGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$catalogPath = __DIR__.'/maqam_catalog.json';
$catalog = json_decode(file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);

$colorHex = [
    'WHITE' => '#FFFFFF',
    'BLACK' => '#1A1A1A',
    'GREY' => '#8A8A8A',
    'CHAMPAGNE' => '#E8D5B7',
];

$defaultPrice = 0;
$defaultStock = 0;
$skuGenerator = app(ProductSkuGenerator::class);

$deletedProducts = 0;
$deletedCategories = 0;
$created = 0;
$variants = 0;

$connection = config('database.default');
$dbConfig = config("database.connections.{$connection}");

DB::transaction(function () use (
    $catalog,
    $colorHex,
    $defaultPrice,
    $defaultStock,
    $skuGenerator,
    &$deletedProducts,
    &$deletedCategories,
    &$created,
    &$variants,
    &$category
) {
    // 1) Wipe previous store catalog (seed + any old imports). Admin/users untouched.
    $deletedProducts = Product::query()->count();
    $deletedCategories = Category::query()->count();

    // Child rows cascade via FK; clear products first so category delete is clean.
    if (Schema::hasTable('product_options')) {
        DB::table('product_options')->delete();
    }
    if (Schema::hasTable('product_colors')) {
        DB::table('product_colors')->delete();
    }
    if (Schema::hasTable('product_images')) {
        DB::table('product_images')->delete();
    }

    // Order/cart lines that point at old demo products
    if (Schema::hasTable('order_items')) {
        DB::table('order_items')->delete();
    }
    if (Schema::hasTable('cart_items')) {
        DB::table('cart_items')->delete();
    }

    // Nullable FKs → detach before product delete
    if (Schema::hasTable('coupons') && Schema::hasColumn('coupons', 'product_id')) {
        DB::table('coupons')->whereNotNull('product_id')->update(['product_id' => null]);
    }
    if (Schema::hasTable('wheel_prizes') && Schema::hasColumn('wheel_prizes', 'product_id')) {
        DB::table('wheel_prizes')->whereNotNull('product_id')->update(['product_id' => null]);
    }
    if (Schema::hasTable('customer_rewards') && Schema::hasColumn('customer_rewards', 'product_id')) {
        DB::table('customer_rewards')->whereNotNull('product_id')->update(['product_id' => null]);
    }

    Product::query()->delete();
    Category::query()->delete();

    // 2) Create the real category from this script
    $category = Category::create([
        'slug' => 'maqam-switches',
        'name_en' => 'Switches & Sockets',
        'name_ar' => 'مفاتيح وبرايز',
        'is_active' => true,
    ]);

    // 3) Import catalog variants as products
    $allVariants = [];
    foreach ($catalog['products'] as $item) {
        foreach ($item['variants'] as $variant) {
            $allVariants[] = ['item' => $item, 'variant' => $variant];
            $variants++;
        }
    }

    $skuPool = $skuGenerator->nextMany($variants);
    $skuIndex = 0;

    foreach ($allVariants as $row) {
        $item = $row['item'];
        $variant = $row['variant'];
        $productionCode = (string) $variant['production_code'];
        $colorEn = trim((string) $variant['color_en']);
        $colorAr = trim((string) $variant['color_ar']);
        $hex = $colorHex[strtoupper($colorEn)] ?? null;
        $catalogCode = $skuGenerator->catalogCode($item['product_name'], $colorEn);
        $sku = $skuPool[$skuIndex++];

        $product = Product::create([
            'category_id' => $category->id,
            'name_en' => $item['product_name'].' - '.$colorEn,
            'name_ar' => $item['description_ar'].' - '.$colorAr,
            'description_en' => $item['product_name'],
            'description_ar' => $item['description_ar'],
            'price' => $defaultPrice,
            'stock_quantity' => $defaultStock,
            'sku' => $sku,
            'is_active' => true,
        ]);
        $created++;

        $product->colors()->create([
            'name' => $colorEn,
            'hex' => $hex,
            'sort_order' => 0,
        ]);

        $options = [
            ['name' => 'system_code', 'value' => (string) $variant['system_code'], 'sort_order' => 0],
            ['name' => 'production_code', 'value' => $productionCode, 'sort_order' => 1],
            ['name' => 'catalog_code', 'value' => $catalogCode, 'sort_order' => 2],
            ['name' => 'product_no', 'value' => (string) $item['no'], 'sort_order' => 3],
            ['name' => 'color_ar', 'value' => $colorAr, 'sort_order' => 4],
        ];

        if (! empty($variant['note'])) {
            $options[] = ['name' => 'note', 'value' => (string) $variant['note'], 'sort_order' => 5];
        }

        $product->options()->createMany($options);
    }
});

dump([
    'db_connection' => $connection,
    'db_name' => $dbConfig['database'] ?? null,
    'wiped' => [
        'products' => $deletedProducts,
        'categories' => $deletedCategories,
    ],
    'kept' => 'admins / users / QR / prizes / ranks (untouched)',
    'category' => [
        'id' => $category->id,
        'slug' => $category->slug,
        'name_ar' => $category->name_ar,
    ],
    'imported_products' => $created,
    'expected_total' => $catalog['total_items'] ?? null,
    'categories_now' => Category::count(),
    'products_now' => Product::count(),
    'sample' => tap(
        Product::query()
            ->whereHas('options', fn ($q) => $q->where('name', 'production_code')->where('value', 'Q5001'))
            ->with(['options' => fn ($q) => $q->whereIn('name', ['catalog_code', 'production_code'])])
            ->first(['id', 'sku', 'name_ar', 'name_en']),
        function ($product) {
            if ($product) {
                $product->setAttribute('options_map', $product->options->pluck('value', 'name'));
            }
        }
    )?->only(['id', 'sku', 'name_ar', 'name_en', 'options_map']),
]);
