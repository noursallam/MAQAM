<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Exceptions\QrScanException;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Services\MediaService;
use App\Services\QrScanService;
use App\Services\ScanRiskGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScanController extends ApiController
{
    public function __construct(
        protected QrScanService $scans,
        protected ScanRiskGuard $risk,
    ) {}

    /**
     * Dry run: what would this code give? Nothing is changed.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate($this->codeRules());

        $customer = $this->customer($request);
        $this->risk->ensureNotLocked($customer);
        $merchant = $this->resolveMerchant($validated['merchant_code'] ?? null, $customer);

        $preview = $this->guarded($customer, fn () => $this->scans->preview($validated['serial_code'], $customer, $merchant));

        return $this->ok([
            'serial_code' => $preview['serial'],
            'status' => $preview['status'],
            'category' => $preview['category'],
            'points_customer' => $preview['points_customer'],
            'points_merchant' => $preview['points_merchant'],
            'merchant_name' => $preview['merchant'],
            'balance_now' => $preview['balance_now'],
            'balance_after' => $preview['balance_after_if_real'],
        ], __('api.qr.preview_ok', ['points' => $preview['points_customer']]));
    }

    /**
     * Claim a code: awards the points and burns the code.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate($this->codeRules() + $this->locationRules() + [
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = $this->customer($request);
        $this->risk->ensureNotLocked($customer);
        $merchant = $this->resolveMerchant($validated['merchant_code'] ?? null, $customer);

        $result = $this->guarded($customer, fn () => $this->scans->scan($validated['serial_code'], $customer, $merchant, false, [
            'lat' => $validated['latitude'] ?? null,
            'lng' => $validated['longitude'] ?? null,
            'device_id' => $validated['device_id'] ?? $request->header('X-Device-Id', 'mobile-app'),
        ]));

        /** @var QrScan $scan */
        $scan = $result['scan'];
        $frozen = $this->risk->freezeIfImpossibleTravel($result['customer'], $scan);

        return $this->ok([
            'scan_id' => $scan->id,
            'points_awarded' => (int) $scan->points_awarded_customer,
            'new_balance' => (int) $result['customer']->points_balance,
            'total_earned' => (int) $result['customer']->total_points_earned,
            'merchant_credited' => $merchant?->business_name,
            'merchant_points' => (int) $scan->points_awarded_merchant,
            'account_frozen' => $frozen,
        ], __('api.qr.scan_ok', ['points' => $scan->points_awarded_customer]));
    }

    /**
     * Upload scans captured offline. Each one succeeds or fails on its own.
     */
    public function syncBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'scans' => ['required', 'array', 'min:1', 'max:200'],
            'scans.*.serial_code' => ['required', 'string', 'max:32'],
            'scans.*.merchant_code' => ['nullable', 'string', 'max:50'],
            'scans.*.scanned_at' => ['required', 'date'],
            'scans.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'scans.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $customer = $this->customer($request);
        $this->risk->ensureNotLocked($customer);

        $results = [];
        $synced = 0;
        $points = 0;

        foreach ($validated['scans'] as $row) {
            $serial = trim($row['serial_code']);

            try {
                $merchant = $this->resolveMerchant($row['merchant_code'] ?? null, $customer);
                $result = $this->scans->scan($serial, $customer, $merchant, true, [
                    'lat' => $row['latitude'] ?? null,
                    'lng' => $row['longitude'] ?? null,
                    'device_id' => $validated['device_id'] ?? $request->header('X-Device-Id', 'mobile-app'),
                    'scanned_at' => $this->trustedScanTime($row['scanned_at']),
                ]);

                $awarded = (int) $result['scan']->points_awarded_customer;
                $synced++;
                $points += $awarded;
                $results[] = ['serial_code' => $serial, 'status' => 'success', 'points' => $awarded, 'error_code' => null, 'message' => null];
            } catch (QrScanException $e) {
                // A retry of a scan we already credited is not an error for the app's queue
                if ($e->reason === QrScanException::ALREADY_USED && $this->alreadyCreditedTo($serial, $customer)) {
                    $results[] = ['serial_code' => $serial, 'status' => 'already_synced', 'points' => 0, 'error_code' => null, 'message' => null];

                    continue;
                }

                if ($e->reason === QrScanException::NOT_FOUND) {
                    $this->risk->recordInvalidCode($customer);
                }

                $results[] = ['serial_code' => $serial, 'status' => 'failed', 'points' => 0, 'error_code' => $e->reason, 'message' => $e->getMessage()];
            } catch (ApiException $e) {
                $results[] = ['serial_code' => $serial, 'status' => 'failed', 'points' => 0, 'error_code' => $e->errorCode, 'message' => $e->getMessage()];
            }
        }

        return $this->ok([
            'total_synced' => $synced,
            'total_failed' => count(array_filter($results, fn ($r) => $r['status'] === 'failed')),
            'total_points_gained' => $points,
            'current_balance' => (int) $customer->fresh()->points_balance,
            'results' => $results,
        ], __('api.qr.sync_ok', ['count' => $synced]));
    }

    /**
     * Merchants this customer has scanned with before, most recent first.
     */
    public function recentMerchants(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        $lastScans = QrScan::where('customer_id', $customer->id)
            ->whereNotNull('merchant_id')
            ->selectRaw('merchant_id, MAX(scanned_at) as last_scanned_at')
            ->groupBy('merchant_id')
            ->orderByDesc('last_scanned_at')
            ->limit(20)
            ->pluck('last_scanned_at', 'merchant_id');

        $merchants = Merchant::whereIn('id', $lastScans->keys())->where('is_approved', true)->get()->keyBy('id');

        return $this->ok($lastScans->keys()
            ->filter(fn ($id) => $merchants->has($id))
            ->map(fn ($id) => [
                'merchant_code' => $merchants[$id]->merchant_code,
                'business_name' => $merchants[$id]->business_name,
                'logo_url' => $merchants[$id]->logo_url ? app(MediaService::class)->url($merchants[$id]->logo_url) : null,
                'last_scanned_at' => Carbon::parse($lastScans[$id])->toIso8601String(),
            ])->values());
    }

    private function codeRules(): array
    {
        return [
            'serial_code' => ['required', 'string', 'max:32'],
            'merchant_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function locationRules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    private function resolveMerchant(?string $code, Customer $customer): ?Merchant
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $merchant = Merchant::where('merchant_code', trim($code))->where('is_approved', true)->first();

        if (! $merchant) {
            throw new ApiException('MERCHANT_NOT_FOUND', __('api.qr.merchant_not_found'), 422);
        }

        // A merchant cannot farm merchant points from their own scans
        if ($merchant->user_id === $customer->user_id) {
            throw new ApiException('MERCHANT_SELF_SCAN', __('api.qr.self_scan'), 422);
        }

        return $merchant;
    }

    /**
     * Run a scan operation, translating scan failures into API errors and counting bad guesses.
     */
    private function guarded(Customer $customer, callable $operation): array
    {
        try {
            return $operation();
        } catch (QrScanException $e) {
            if ($e->reason === QrScanException::NOT_FOUND) {
                $this->risk->recordInvalidCode($customer);
            }

            throw new ApiException($e->reason, $e->getMessage(), $e->reason === QrScanException::NOT_FOUND ? 404 : 422);
        }
    }

    private function alreadyCreditedTo(string $serial, Customer $customer): bool
    {
        return QrCode::where('serial_code', $serial)->where('used_by_customer_id', $customer->id)->exists();
    }

    /**
     * The device clock is untrusted: never accept the future or anything older than 30 days.
     */
    private function trustedScanTime(string $scannedAt): Carbon
    {
        $time = Carbon::parse($scannedAt);

        return $time->isFuture() || $time->lt(now()->subDays(30)) ? now() : $time;
    }
}
