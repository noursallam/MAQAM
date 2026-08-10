<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSkuGenerator
{
    public const BRAND = 'MQM';

    /**
     * Manufacturer-style Code 128 SKU from product name + color.
     * Example: MQM-SW-1G1W24-WHT
     */
    public function makeFromCatalog(string $productName, ?string $colorEn = null, ?int $ignoreProductId = null): string
    {
        return $this->ensureUnique(
            $this->catalogCode($productName, $colorEn),
            $ignoreProductId
        );
    }

    /**
     * Build SKU from admin product fields (auto or leave blank on form).
     */
    public function makeFromProductData(
        string $nameEn,
        ?string $colorEn = null,
        ?Category $category = null,
        ?int $ignoreProductId = null
    ): string {
        [$baseName, $inferredColor] = $this->splitNameAndColor($nameEn);
        $color = $colorEn ?: $inferredColor;

        return $this->makeFromCatalog($baseName, $color, $ignoreProductId);
    }

    /**
     * Human-readable catalog / SKU stem.
     * Example: MQM-SW-1G1W24-WHT
     */
    public function catalogCode(string $productName, ?string $colorEn = null): string
    {
        [$baseName, $inferredColor] = $this->splitNameAndColor($productName);
        $colorEn = $colorEn ?: $inferredColor;

        $brand = self::BRAND;
        $type = $this->typeFromProductName($baseName);
        $model = $this->modelFromProductName($baseName);
        $color = $this->colorCode($colorEn);

        return $color
            ? "{$brand}-{$type}-{$model}-{$color}"
            : "{$brand}-{$type}-{$model}";
    }

    public function ensureUnique(string $base, ?int $ignoreProductId = null): string
    {
        $base = strtoupper(trim($base));
        $candidate = $base;
        $suffix = 2;

        while ($this->skuExists($candidate, $ignoreProductId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function isStructuredSku(?string $sku): bool
    {
        return is_string($sku) && preg_match('/^MQM-[A-Z0-9]+-[A-Z0-9]+(-[A-Z0-9]+)?$/i', $sku) === 1;
    }

    /** @deprecated numeric barcodes replaced by Code128 catalog SKUs */
    public function isBarcodeSku(?string $sku): bool
    {
        return is_string($sku) && $sku !== '';
    }

    /** @deprecated */
    public function isAppSku(?string $sku): bool
    {
        return $this->isBarcodeSku($sku);
    }

    /** @deprecated random digits no longer used for product SKUs */
    public function next(?int $ignoreProductId = null): string
    {
        return $this->makeFromCatalog('ITEM', null, $ignoreProductId);
    }

    /**
     * @return list<string>
     * @deprecated
     */
    public function nextMany(int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $this->ensureUnique(self::BRAND.'-TMP-'.strtoupper(Str::random(6)));
        }

        return $out;
    }

    public function colorCode(?string $color): ?string
    {
        if ($color === null || trim($color) === '') {
            return null;
        }

        $key = strtoupper(trim($color));

        return match ($key) {
            'WHITE', 'WHT', 'WH', 'أبيض', 'ابيض' => 'WHT',
            'BLACK', 'BLK', 'اسمر', 'أسود' => 'BLK',
            'GREY', 'GRAY', 'GRY', 'رصاصى', 'رصاصي' => 'GRY',
            'CHAMPAGNE', 'CHP', 'BEIGE', 'بيج' => 'CHP',
            default => strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '', $key) ?: 'CLR', 0, 3)),
        };
    }

    public function typeFromProductName(string $name): string
    {
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'dimmer') => 'DMR',
            str_contains($n, 'sensor') => 'SNS',
            str_contains($n, 'socket') || str_contains($n, 'tel') || str_contains($n, 'computer') || str_contains($n, 'usb') || str_contains($n, 'satellite') => 'SKT',
            str_contains($n, 'blank') || str_contains($n, 'frame') || str_contains($n, 'water') => 'ACC',
            str_contains($n, 'bell') || str_contains($n, 'doorbell') => 'BEL',
            default => 'SW',
        };
    }

    public function modelFromProductName(string $name): string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));

        $map = [
            '1gang 1way(1/3)' => '1G1W24',
            '1gang 2way (1/3)' => '1G2W24',
            '2gang 1way(1/3)' => '2G1W24',
            '1gang 3way (1/3) new' => '1G3W24',
            '20a switch(1/3)' => '20A24',
            '20a switch(l)' => '20AL',
            '45a switch(l)' => '45AL',
            '1gang 1way(l)' => '1G1WL',
            'doorbell switch(1/3)' => 'BELL24',
            'doorbell switch(l)' => 'BELLL',
            '2p socket(1/3)' => '2P24',
            'mf socket(2/3)' => 'MF24',
            'european socket 2/3' => 'EU48',
            'tel(1/3)' => 'TEL24',
            'computer(1/3)' => 'PC24',
            'satellite socket(1/3)' => 'SAT24',
            'speed dimmer(1/3)' => 'SPD24',
            'light dimmer(1/3)' => 'LGT24',
            'voice dimmer(1/3)' => 'VOX24',
            'usb +type c socket(1/3)' => 'USBC24',
            'emergency switch(1/3)' => 'EMG24',
            'emergency switch(3/3)' => 'EMG72',
            'blank plate(1/3) without small box' => 'BLANK24',
            'blank plate(12mm) without small box' => 'BLANK12',
            'big frame' => 'FRAME3',
            'curtain switch(1/3)' => 'CURT24',
            'bird invoice bell (48mm)' => 'RING48',
            'body sensor light switch(48mm)' => 'PIR48',
            'water proof' => 'WPRF',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $model = strtoupper($name);
        $model = str_replace(['(1/3)', '(2/3)', '(3/3)', '(L)', '(l)'], ['24', '48', '72', 'L', 'L'], $model);
        $model = preg_replace('/[^A-Z0-9]+/', '', $model) ?: 'ITEM';

        return Str::substr($model, 0, 12);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    protected function splitNameAndColor(string $nameEn): array
    {
        if (! str_contains($nameEn, ' - ')) {
            return [trim($nameEn), null];
        }

        [$base, $color] = explode(' - ', $nameEn, 2);

        return [trim($base), trim($color) !== '' ? trim($color) : null];
    }

    protected function skuExists(string $sku, ?int $ignoreProductId = null): bool
    {
        return Product::query()
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->where('sku', $sku)
            ->exists();
    }
}
