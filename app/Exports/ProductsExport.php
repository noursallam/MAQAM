<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()->with('category')->latest();
    }

    public function headings(): array
    {
        return ['الاسم (عربي)', 'الاسم (إنجليزي)', 'SKU', 'السعر', 'الكمية', 'القسم', 'تاريخ الإضافة'];
    }

    public function map($product): array
    {
        return [
            $product->name_ar,
            $product->name_en,
            $product->sku,
            $product->price,
            $product->stock_quantity,
            $product->category?->name_ar,
            $product->created_at?->format('Y-m-d'),
        ];
    }
}
