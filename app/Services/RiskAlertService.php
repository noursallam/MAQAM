<?php

namespace App\Services;

use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Collection;

class RiskAlertService
{
    /**
     * @return array{
     *     total: int,
     *     frozen: int,
     *     geo: int,
     *     failed_sync: int,
     *     items: list<array{severity: string, title: string, detail: string}>
     * }
     */
    public function summary(int $limit = 5): array
    {
        $frozenUsers = User::with('customer')
            ->where('role', 'customer')
            ->where('is_active', false)
            ->latest()
            ->take($limit)
            ->get();

        $failedSyncCount = QrScan::where('sync_status', 'failed')->count();

        $geo = $this->geoAnomalies()->take($limit);
        $items = [];

        foreach ($frozenUsers as $user) {
            $items[] = [
                'severity' => 'high',
                'title' => $user->full_name ?: ($user->phone_number ?: '#'.$user->id),
                'detail' => __('admin.risk.alert_frozen'),
            ];
        }

        foreach ($geo as $row) {
            $name = $row['customer']?->user?->full_name ?: __('admin.risk.unknown_user');
            $items[] = [
                'severity' => $row['severity'],
                'title' => $name,
                'detail' => __('admin.risk.alert_geo', [
                    'km' => $row['distance_km'],
                    'min' => $row['minutes'],
                    'speed' => $row['speed'],
                ]),
            ];
        }

        if ($failedSyncCount > 0) {
            $items[] = [
                'severity' => 'medium',
                'title' => __('admin.home.failed_sync'),
                'detail' => __('admin.risk.alert_failed_sync', ['count' => $failedSyncCount]),
            ];
        }

        $frozenCount = User::where('role', 'customer')->where('is_active', false)->count();
        $geoCount = $this->geoAnomalies()->count();

        return [
            'total' => $frozenCount + $geoCount + ($failedSyncCount > 0 ? 1 : 0),
            'frozen' => $frozenCount,
            'geo' => $geoCount,
            'failed_sync' => $failedSyncCount,
            'items' => array_slice($items, 0, $limit),
        ];
    }

    public function geoAnomalies(): Collection
    {
        return QrScan::with(['customer.user'])
            ->whereNotNull('scan_location_lat')
            ->whereNotNull('scan_location_lng')
            ->latest('scanned_at')
            ->take(40)
            ->get()
            ->groupBy('customer_id')
            ->filter(fn ($group) => $group->count() >= 2)
            ->map(function ($group) {
                $sorted = $group->sortByDesc('scanned_at')->values();
                $a = $sorted[0];
                $b = $sorted[1] ?? null;
                if (! $b) {
                    return null;
                }

                $distance = $this->haversineKm(
                    (float) $a->scan_location_lat,
                    (float) $a->scan_location_lng,
                    (float) $b->scan_location_lat,
                    (float) $b->scan_location_lng,
                );
                $minutes = max(1, abs($a->scanned_at->diffInMinutes($b->scanned_at)));
                $speed = $distance / ($minutes / 60);

                if ($speed < 200) {
                    return null;
                }

                return [
                    'customer' => $a->customer,
                    'distance_km' => round($distance, 1),
                    'minutes' => $minutes,
                    'speed' => round($speed),
                    'severity' => $speed > 800 ? 'high' : 'medium',
                    'scan' => $a,
                ];
            })
            ->filter()
            ->values();
    }

    public function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
