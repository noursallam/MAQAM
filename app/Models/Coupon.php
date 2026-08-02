<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const ASSIGNMENT_PUBLIC_CODE = 'public_code';

    public const ASSIGNMENT_PERSONAL_GRANT = 'personal_grant';

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'scope', 'assignment',
        'merchant_id', 'category_id', 'product_id', 'valid_from', 'valid_to',
        'usage_limit', 'usage_limit_per_customer', 'used_count',
        'min_order_amount', 'max_discount_amount', 'is_active', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(CustomerReward::class);
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->gt($this->valid_to)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isPublicCode(): bool
    {
        return $this->assignment === self::ASSIGNMENT_PUBLIC_CODE && $this->is_public;
    }
}
