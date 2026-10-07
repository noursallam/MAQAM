<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\QrScan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Anti-fraud checks applied to scans coming from the mobile app.
 */
class ScanRiskGuard
{
    private const MAX_FAILED_CODES = 5;
    private const LOCK_SECONDS = 2 * 60 * 60;

    // Faster than any real journey, over a distance GPS jitter cannot explain
    private const IMPOSSIBLE_SPEED_KMH = 800;
    private const MIN_DISTANCE_KM = 100;

    public function __construct(
        protected RiskAlertService $riskAlerts
    ) {}

    /**
     * Refuse scanning while the customer is locked out for guessing codes.
     */
    public function ensureNotLocked(Customer $customer): void
    {
        $key = $this->key($customer);

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_CODES)) {
            throw new ApiException(
                'SCAN_LOCKED',
                __('api.qr.locked', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
                429
            );
        }
    }

    /**
     * Count a non-existent code against the customer.
     */
    public function recordInvalidCode(Customer $customer): void
    {
        RateLimiter::hit($this->key($customer), self::LOCK_SECONDS);
    }

    /**
     * Freeze the account when two live scans are impossibly far apart. Returns true when frozen.
     */
    public function freezeIfImpossibleTravel(Customer $customer, QrScan $scan): bool
    {
        if (! is_numeric($scan->scan_location_lat) || ! is_numeric($scan->scan_location_lng)) {
            return false;
        }

        $previous = QrScan::where('customer_id', $customer->id)
            ->where('id', '!=', $scan->id)
            ->where('is_offline', false)
            ->whereNotNull('scan_location_lat')
            ->whereNotNull('scan_location_lng')
            ->where('device_id', '!=', 'admin-sim')
            ->latest('scanned_at')
            ->first();

        if (! $previous || ! is_numeric($previous->scan_location_lat) || ! is_numeric($previous->scan_location_lng)) {
            return false;
        }

        $distance = $this->riskAlerts->haversineKm(
            (float) $scan->scan_location_lat,
            (float) $scan->scan_location_lng,
            (float) $previous->scan_location_lat,
            (float) $previous->scan_location_lng,
        );
        $hours = max(1, abs($scan->scanned_at->diffInSeconds($previous->scanned_at))) / 3600;

        if ($distance < self::MIN_DISTANCE_KM || $distance / $hours < self::IMPOSSIBLE_SPEED_KMH) {
            return false;
        }

        $user = $customer->user;
        $user->forceFill(['is_active' => false])->save();
        $user->tokens()->delete();
        $user->deviceTokens()->delete();

        Log::warning('Account frozen: geo-velocity violation', [
            'user_id' => $user->id,
            'distance_km' => round($distance, 1),
            'speed_kmh' => round($distance / $hours),
        ]);

        return true;
    }

    private function key(Customer $customer): string
    {
        return 'qr-invalid:'.$customer->id;
    }
}
