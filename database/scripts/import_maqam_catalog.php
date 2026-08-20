<?php

/**
 * Reset store catalog to MAQAM switches/sockets only, then import.
 *
 * - Deletes ALL store categories + products (seed leftovers too)
 * - Does NOT touch admins, users, QR, prizes, ranks, merchants, …
 * - One Product row per color variant (113 total)
 * - sku = manufacturer Code128 catalog code (MQM-SW-1G1W24-WHT) — auto from product+color
 * - production_code / system_code / catalog_code kept in sync
 * - colors & options left EMPTY (each color is already its own product)
 *
 * Run:
 *   php artisan migrate
 *   php artisan tinker --execute="require base_path('database/scripts/import_maqam_catalog.php');"
 */

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSkuGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$catalogPath = __DIR__.'/maqam_catalog.json';
$catalog = json_decode(file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);

$defaultPrice = 0;
$defaultStock = 0;
$skuGenerator = app(ProductSkuGenerator::class);

$deletedProducts = 0;
$deletedCategories = 0;
$created = 0;
$variants = 0;

$connection = config('database.default');
$dbConfig = config("database.connections.{$connection}");

if (! Schema::hasColumn('products', 'production_code')) {
    throw new RuntimeException(
        'Missing products.production_code column. Run: php artisan migrate'
    );
}

DB::transaction(function () use (
    $catalog,
    $defaultPrice,
    $defaultStock,
    $skuGenerator,
    &$deletedProducts,
    &$deletedCategories,
    &$created,
    &$variants,
    &$category
) {
    $deletedProducts = Product::query()->count();
    $deletedCategories = Category::query()->count();

    if (Schema::hasTable('product_options')) {
        DB::table('product_options')->delete();
    }
    if (Schema::hasTable('product_colors')) {
        DB::table('product_colors')->delete();
    }
    if (Schema::hasTable('product_images')) {
        DB::table('product_images')->delete();
    }
    if (Schema::hasTable('order_items')) {
        DB::table('order_items')->delete();
    }
    if (Schema::hasTable('cart_items')) {
        DB::table('cart_items')->delete();
    }
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

    $category = Category::create([
        'slug' => 'maqam-switches',
        'name_en' => 'Switches & Sockets',
        'name_ar' => 'مفاتيح وبرايز',
        'is_active' => true,
    ]);

    foreach ($catalog['products'] as $item) {
        foreach ($item['variants'] as $variant) {
            $variants++;
            $productionCode = (string) $variant['production_code'];
            $colorEn = trim((string) $variant['color_en']);
            $colorAr = trim((string) $variant['color_ar']);
            $catalogCode = $skuGenerator->catalogCode($item['product_name'], $colorEn);
            $sku = $skuGenerator->makeFromCatalog($item['product_name'], $colorEn);

            $descriptionAr = $item['description_ar'];
            if (! empty($variant['note'])) {
                $descriptionAr .= ' — '.$variant['note'];
            }

            Product::create([
                'category_id' => $category->id,
                'name_en' => $item['product_name'].' - '.$colorEn,
                'name_ar' => $item['description_ar'].' - '.$colorAr,
                'description_en' => $item['product_name'],
                'description_ar' => $descriptionAr,
                'price' => $defaultPrice,
                'stock_quantity' => $defaultStock,
                'sku' => $sku,
                'production_code' => $productionCode,
                'system_code' => (int) $variant['system_code'],
                'catalog_code' => $catalogCode,
                'is_active' => true,
            ]);
            $created++;
        }
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
    'note' => '1 product per color (= 113). SKU = Code128 catalog code from product+color.',
    'category' => [
        'id' => $category->id,
        'slug' => $category->slug,
        'name_ar' => $category->name_ar,
    ],
    'imported_products' => $created,
    'expected_total' => $catalog['total_items'] ?? null,
    'categories_now' => Category::count(),
    'products_now' => Product::count(),
    'options_rows' => DB::table('product_options')->count(),
    'sample' => Product::query()
        ->where('production_code', 'Q5001')
        ->first(['id', 'sku', 'production_code', 'system_code', 'catalog_code', 'name_ar', 'name_en'])
        ?->toArray(),
]);
