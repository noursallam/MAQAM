<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskController extends Controller
{
    public function index(): View
    {
        $frozen = User::with('customer.rank')
            ->where('role', 'customer')
            ->where('is_active', false)
            ->latest()
            ->get()
            ->map(function (User $user) {
                return [
                    'user' => $user,
                    'severity' => 'high',
                    'reason' => 'حساب مجمّد / نشاط مشبوه',
                ];
            });

        $failedSync = QrScan::with(['customer.user', 'qrCode'])
            ->where('sync_status', 'failed')
            ->latest('scanned_at')
            ->take(20)
            ->get();

        $geoAnomalies = QrScan::with(['customer.user'])
            ->whereNotNull('scan_location_lat')
            ->whereNotNull('scan_location_lng')
            ->latest('scanned_at')
            ->take(30)
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

        return view('admin.risk.index', [
            'frozen' => $frozen,
            'failedSync' => $failedSync,
            'geoAnomalies' => $geoAnomalies,
        ]);
    }

    public function freeze(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        abort_unless($user->role === 'customer', 422);

        $user->update(['is_active' => false]);

        return back()->with('success', __('admin.risk.freeze').' — '.$data['reason']);
    }

    public function unfreeze(User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 422);
        $user->update(['is_active' => true]);

        return back()->with('success', __('admin.risk.unfreeze'));
    }

    protected function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
