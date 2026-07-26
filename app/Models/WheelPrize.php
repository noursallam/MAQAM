<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WheelPrize extends Model
{
    protected $fillable = [
        'type', 'label_ar', 'label_en', 'weight', 'points_amount',
        'coupon_id', 'product_id', 'discount_type', 'discount_value',
        'stock_limit', 'awarded_count', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'points_amount' => 'integer',
            'discount_value' => 'decimal:2',
            'stock_limit' => 'integer',
            'awarded_count' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function spins(): HasMany
    {
        return $this->hasMany(WheelSpin::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('weight', '>', 0)
            ->where(function (Builder $q) {
                $q->whereNull('stock_limit')
                    ->orWhereColumn('awarded_count', '<', 'stock_limit');
            });
    }

    public function hasStock(): bool
    {
        if ($this->stock_limit === null) {
            return true;
        }

        return $this->awarded_count < $this->stock_limit;
    }

    public function relativePercent(int $totalWeight): float
    {
        if ($totalWeight <= 0) {
            return 0;
        }

        return round(($this->weight / $totalWeight) * 100, 1);
    }

    public function displayLabel(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'ar' ? $this->label_ar : $this->label_en;
    }
}
