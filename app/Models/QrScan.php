<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScan extends Model
{
    protected $fillable = [
        'qr_code_id', 'customer_id', 'merchant_id', 'points_awarded_customer',
        'points_awarded_merchant', 'scan_location_lat', 'scan_location_lng',
        'scanned_at', 'is_offline', 'sync_status', 'device_id',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'is_offline' => 'boolean',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
