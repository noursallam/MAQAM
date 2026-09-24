<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name_en', 'name_ar', 'description_en', 'description_ar',
        'price', 'stock_quantity', 'sku', 'production_code', 'system_code', 'catalog_code',
        'image_path', 'image_url', 'is_active', 'weight', 'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function thumbnail(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_thumbnail', true);
    }

    public function thumbnailUrl(): ?string
    {
        $thumb = $this->relationLoaded('thumbnail')
            ? $this->thumbnail
            : ($this->relationLoaded('images')
                ? $this->images->firstWhere('is_thumbnail', true) ?? $this->images->first()
                : $this->thumbnail()->first() ?? $this->images()->first());

        if ($thumb) {
            return $thumb->url();
        }

        $mediaService = app(\App\Services\MediaService::class);

        if (!empty($this->image_path)) {
            return $mediaService->url($this->image_path);
        }
        
        if (!empty($this->image_url)) {
            return $mediaService->url($this->image_url);
        }

        return null;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->thumbnailUrl() ?? asset('identity/MAQAM-24.jpg');
    }

    public function hasOptionPricing(): bool
    {
        $opts = $this->relationLoaded('options') ? $this->options : $this->options()->get();
        return $opts->contains(fn($o) => $o->price !== null && $o->price > 0);
    }

    public function minPrice(): float
    {
        $opts = $this->relationLoaded('options') ? $this->options : $this->options()->get();
        $optionPrices = $opts->pluck('price')->filter(fn($p) => $p !== null && (float) $p > 0)->map(fn($p) => (float) $p);

        if ($optionPrices->isNotEmpty()) {
            return (float) $optionPrices->min();
        }

        return (float) $this->price;
    }

    public function maxPrice(): float
    {
        $opts = $this->relationLoaded('options') ? $this->options : $this->options()->get();
        $optionPrices = $opts->pluck('price')->filter(fn($p) => $p !== null && (float) $p > 0)->map(fn($p) => (float) $p);

        if ($optionPrices->isNotEmpty()) {
            return (float) $optionPrices->max();
        }

        return (float) $this->price;
    }

    public function isPriceVariable(): bool
    {
        $opts = $this->relationLoaded('options') ? $this->options : $this->options()->get();
        $optionPrices = $opts->pluck('price')->filter(fn($p) => $p !== null && (float) $p > 0);

        if ($optionPrices->count() > 1 && $this->minPrice() < $this->maxPrice()) {
            return true;
        }

        if ($optionPrices->isNotEmpty() && (float) $this->price > 0 && abs($this->minPrice() - (float) $this->price) > 0.001) {
            return true;
        }

        return false;
    }
}
