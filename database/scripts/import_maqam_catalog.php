<?php

/**
 * Import MAQAM catalog (one Product row per color SKU).
 *
 * Run:
 *   php artisan tinker
 *   >>> require base_path('database/scripts/import_maqam_catalog.php');
 *
 * Or one-shot:
 *   php artisan tinker --execute="require base_path('database/scripts/import_maqam_catalog.php');"
 *
 * Defaults: price=0, stock=0. Re-run is safe (updateOrCreate by sku).
 */

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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

$category = Category::firstOrCreate(
    ['slug' => 'maqam-switches'],
    [
        'name_en' => 'Switches & Sockets',
        'name_ar' => 'مفاتيح وبرايز',
        'is_active' => true,
    ]
);

$created = 0;
$updated = 0;
$variants = 0;

DB::transaction(function () use ($catalog, $category, $colorHex, $defaultPrice, $defaultStock, &$created, &$updated, &$variants) {
    foreach ($catalog['products'] as $item) {
        foreach ($item['variants'] as $variant) {
            $variants++;
            $sku = (string) $variant['production_code'];
            $colorEn = trim((string) $variant['color_en']);
            $colorAr = trim((string) $variant['color_ar']);
            $hex = $colorHex[strtoupper($colorEn)] ?? null;

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name_en' => $item['product_name'].' - '.$colorEn,
                    'name_ar' => $item['description_ar'].' - '.$colorAr,
                    'description_en' => $item['product_name'],
                    'description_ar' => $item['description_ar'],
                    'price' => $defaultPrice,
                    'stock_quantity' => $defaultStock,
                    'is_active' => true,
                ]
            );

            $product->wasRecentlyCreated ? $created++ : $updated++;

            $product->colors()->delete();
            $product->colors()->create([
                'name' => $colorEn,
                'hex' => $hex,
                'sort_order' => 0,
            ]);

            $product->options()->delete();
            $options = [
                ['name' => 'system_code', 'value' => (string) $variant['system_code'], 'sort_order' => 0],
                ['name' => 'production_code', 'value' => $sku, 'sort_order' => 1],
                ['name' => 'product_no', 'value' => (string) $item['no'], 'sort_order' => 2],
                ['name' => 'color_ar', 'value' => $colorAr, 'sort_order' => 3],
            ];

            if (! empty($variant['note'])) {
                $options[] = ['name' => 'note', 'value' => (string) $variant['note'], 'sort_order' => 4];
            }

            $product->options()->createMany($options);
        }
    }
});

dump([
    'category_id' => $category->id,
    'category' => $category->name_en,
    'variants_in_file' => $variants,
    'expected_total' => $catalog['total_items'] ?? null,
    'created' => $created,
    'updated' => $updated,
    'products_with_q_sku' => Product::where('sku', 'like', 'Q%')->count(),
]);
