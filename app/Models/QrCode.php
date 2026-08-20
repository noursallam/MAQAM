<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    protected $fillable = [
        'serial_code', 'category_id', 'points_awarded', 'status',
        'generated_at', 'printed_at', 'sold_at', 'sold_order_id',
        'used_at', 'used_by_customer_id', 'batch_id',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'printed_at' => 'datetime',
            'sold_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function categoryPrize(): BelongsTo
    {
        return $this->belongsTo(CategoryPrize::class, 'category_id');
    }

    public function usedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'used_by_customer_id');
    }

    public function soldOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sold_order_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    public function lifecycleStatus(): string
    {
        if ($this->status === 'expired') {
            return 'expired';
        }

        if ($this->status === 'used' || $this->used_at) {
            return 'scanned';
        }

        if ($this->sold_at) {
            return 'sold';
        }

        if ($this->printed_at) {
            return 'printed';
        }

        return 'generated';
    }

    public function lifecycleLabel(): string
    {
        return match ($this->lifecycleStatus()) {
            'generated' => __('admin.qr.life_generated'),
            'printed' => __('admin.qr.life_printed'),
            'sold' => __('admin.qr.life_sold'),
            'scanned' => __('admin.qr.life_scanned'),
            'expired' => __('admin.qr.life_expired'),
            default => $this->lifecycleStatus(),
        };
    }
}
