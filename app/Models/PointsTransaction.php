<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    protected $fillable = [
        'customer_id', 'merchant_id', 'qr_scan_id', 'type', 'amount',
        'description', 'balance_after', 'admin_id', 'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function qrScan(): BelongsTo
    {
        return $this->belongsTo(QrScan::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
