<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    protected $fillable = [
        'serial_code', 'category_id', 'points_awarded', 'status',
        'generated_at', 'used_at', 'used_by_customer_id', 'batch_id',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
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

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }
}
