<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Services\QrScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $scans = QrScan::with(['customer.user', 'merchant', 'qrCode.categoryPrize'])
            ->when($request->sync_status, fn ($q, $s) => $q->where('sync_status', $s))
            ->when($request->filled('is_offline'), fn ($q) => $q->where('is_offline', $request->boolean('is_offline')))
            ->when($request->merchant_id, fn ($q, $id) => $q->where('merchant_id', $id))
            ->when($request->q, function ($q, $term) {
                $q->whereHas('qrCode', fn ($c) => $c->where('serial_code', 'like', "%{$term}%"))
                    ->orWhereHas('customer.user', fn ($u) => $u->where('full_name', 'like', "%{$term}%"));
            })
            ->when($request->from, fn ($q, $from) => $q->whereDate('scanned_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('scanned_at', '<=', $to))
            ->latest('scanned_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.scans.index', [
            'scans' => $scans,
            'merchants' => Merchant::where('is_approved', true)->orderBy('business_name')->get(),
            'customers' => Customer::with(['user', 'rank'])->orderByDesc('id')->limit(100)->get(),
            'activeCodes' => QrCode::query()
                ->where('status', 'active')
                ->latest('id')
                ->limit(50)
                ->get(['id', 'serial_code', 'points_awarded', 'batch_id']),
        ]);
    }

    /**
     * Preview-only simulation — does not consume the QR or award points.
     */
    public function simulate(Request $request, QrScanService $scanner): JsonResponse
    {
        $data = $request->validate([
            'serial_code' => ['required', 'string', 'max:16'],
            'customer_id' => ['required', 'exists:customers,id'],
            'merchant_id' => ['nullable', 'exists:merchants,id'],
            'is_offline' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::with(['user', 'rank'])->findOrFail($data['customer_id']);
        $merchant = ! empty($data['merchant_id'])
            ? Merchant::find($data['merchant_id'])
            : null;

        try {
            $preview = $scanner->preview(
                $data['serial_code'],
                $customer,
                $merchant,
                $request->boolean('is_offline'),
            );

            return response()->json($preview);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
