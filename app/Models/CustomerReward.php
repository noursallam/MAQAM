<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReward extends Model
{
    public const TYPE_COUPON = 'coupon';

    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_PRODUCT = 'product';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_USED = 'used';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'customer_id', 'type', 'status', 'source', 'code',
        'coupon_id', 'product_id', 'wheel_spin_id',
        'amount_type', 'amount_value', 'expires_at', 'used_at', 'order_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_value' => 'decimal:2',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wheelSpin(): BelongsTo
    {
        return $this->belongsTo(WheelSpin::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_AVAILABLE)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_AVAILABLE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->type === self::TYPE_COUPON && $this->coupon && ! $this->coupon->isCurrentlyValid()) {
            return false;
        }

        return true;
    }

    public function markUsed(?int $orderId = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_USED,
            'used_at' => now(),
            'order_id' => $orderId,
        ])->save();
    }
}
