<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\QrScan;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        ]);
    }
}
