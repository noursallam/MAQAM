<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantChangeRequest extends Model
{
    protected $fillable = [
        'user_id', 'current_merchant_id', 'requested_merchant_code',
        'requested_merchant_id', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'current_merchant_id');
    }

    public function requestedMerchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'requested_merchant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
