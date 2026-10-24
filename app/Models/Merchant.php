<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'business_address', 'merchant_code',
        'is_approved', 'approved_at', 'approved_by', 'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /** Scans where a customer entered this merchant's code. */
    public function qrScans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    /**
     * What the code has earned, shared by the app and the dashboard.
     *
     * @return array{total_scans: int, total_points: int, scans_this_month: int, points_this_month: int, unique_customers: int, last_scan_at: ?string}
     */
    public function stats(): array
    {
        $month = now()->startOfMonth();

        return [
            'total_scans' => $this->qrScans()->count(),
            'total_points' => (int) $this->qrScans()->sum('points_awarded_merchant'),
            'scans_this_month' => $this->qrScans()->where('scanned_at', '>=', $month)->count(),
            'points_this_month' => (int) $this->qrScans()->where('scanned_at', '>=', $month)->sum('points_awarded_merchant'),
            'unique_customers' => $this->qrScans()->distinct()->count('customer_id'),
            'last_scan_at' => $this->qrScans()->max('scanned_at'),
        ];
    }
}
