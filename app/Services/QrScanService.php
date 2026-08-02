<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\PointsTransaction;
use App\Models\QrCode;
use App\Models\QrScan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QrScanService
{
    /**
     * Dry-run: validate what a scan would do — never mutates QR / points / scans.
     *
     * @return array{
     *   ok: bool,
     *   serial: string,
     *   status: string,
     *   category: ?string,
     *   customer: string,
     *   customer_id: int,
     *   merchant: ?string,
     *   points_customer: int,
     *   points_merchant: int,
     *   balance_now: int,
     *   balance_after_if_real: int,
     *   offline: bool,
     *   message: string
     * }
     */
    public function preview(
        string $serial,
        Customer $customer,
        ?Merchant $merchant = null,
        bool $offline = false,
    ): array {
        $serial = trim($serial);
        $customer->loadMissing(['user', 'rank']);

        $code = QrCode::query()
            ->with('categoryPrize')
            ->where('serial_code', $serial)
            ->first();

        if (! $code) {
            throw new RuntimeException(__('admin.qr.scan_not_found'));
        }

        if ($code->status === 'used') {
            throw new RuntimeException(__('admin.qr.scan_already_used'));
        }

        if ($code->status === 'expired') {
            throw new RuntimeException(__('admin.qr.scan_expired'));
        }

        $customerPoints = (int) $code->points_awarded;
        $merchantPoints = $merchant
            ? (int) ($customer->rank?->merchant_points_per_scan ?? 0)
            : 0;

        return [
            'ok' => true,
            'serial' => $code->serial_code,
            'status' => $code->status,
            'category' => $code->categoryPrize?->name_ar,
            'customer' => $customer->user?->full_name ?? '#'.$customer->id,
            'customer_id' => $customer->id,
            'merchant' => $merchant?->business_name,
            'points_customer' => $customerPoints,
            'points_merchant' => $merchantPoints,
            'balance_now' => (int) $customer->points_balance,
            'balance_after_if_real' => (int) $customer->points_balance + $customerPoints,
            'offline' => $offline,
            'message' => __('admin.qr.scan_preview_ok', [
                'points' => $customerPoints,
                'customer' => $customer->user?->full_name ?? '#'.$customer->id,
            ]),
        ];
    }

    /**
     * Real scan — awards points and marks the QR used.
     *
     * @return array{scan: QrScan, code: QrCode, customer: Customer}
     */
    public function scan(
        string $serial,
        Customer $customer,
        ?Merchant $merchant = null,
        bool $offline = false,
    ): array {
        $serial = trim($serial);

        return DB::transaction(function () use ($serial, $customer, $merchant, $offline) {
            $code = QrCode::query()
                ->with('categoryPrize')
                ->where('serial_code', $serial)
                ->lockForUpdate()
                ->first();

            if (! $code) {
                throw new RuntimeException(__('admin.qr.scan_not_found'));
            }

            if ($code->status === 'used') {
                throw new RuntimeException(__('admin.qr.scan_already_used'));
            }

            if ($code->status === 'expired') {
                throw new RuntimeException(__('admin.qr.scan_expired'));
            }

            $customer = Customer::query()->lockForUpdate()->with('rank')->findOrFail($customer->id);

            $customerPoints = (int) $code->points_awarded;
            $merchantPoints = (int) ($customer->rank?->merchant_points_per_scan ?? 0);
            if (! $merchant) {
                $merchantPoints = 0;
            }

            $customer->points_balance += $customerPoints;
            $customer->total_points_earned += $customerPoints;
            $customer->save();

            $scan = QrScan::create([
                'qr_code_id' => $code->id,
                'customer_id' => $customer->id,
                'merchant_id' => $merchant?->id,
                'points_awarded_customer' => $customerPoints,
                'points_awarded_merchant' => $merchantPoints,
                'scan_location_lat' => '30.0444',
                'scan_location_lng' => '31.2357',
                'scanned_at' => now(),
                'is_offline' => $offline,
                'sync_status' => $offline ? 'pending' : 'synced',
                'device_id' => 'admin-sim',
            ]);

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'merchant_id' => $merchant?->id,
                'qr_scan_id' => $scan->id,
                'type' => 'earn',
                'amount' => $customerPoints,
                'description' => 'مسح QR — '.$code->serial_code,
                'balance_after' => $customer->points_balance,
                'transaction_date' => now(),
            ]);

            $code->update([
                'status' => 'used',
                'used_at' => now(),
                'used_by_customer_id' => $customer->id,
            ]);

            return [
                'scan' => $scan->fresh(['customer.user', 'merchant', 'qrCode.categoryPrize']),
                'code' => $code->fresh(),
                'customer' => $customer->fresh(['user', 'rank']),
            ];
        });
    }
}
