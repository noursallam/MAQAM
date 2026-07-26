<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $ordersPerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'value' => Order::whereDate('created_at', $day)->count(),
        ]);

        $revenuePerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'value' => (float) Order::whereDate('created_at', $day)
                ->whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total_amount'),
        ]);

        $scansPerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'value' => QrScan::whereDate('scanned_at', $day)->count(),
        ]);

        $orderStatusCounts = [
            'new' => Order::where('status', 'new')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
        ];

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])->sum('total_amount'),
                'orders_total' => Order::count(),
                'orders_new' => $orderStatusCounts['new'],
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'products' => Product::count(),
                'low_stock' => Product::where('stock_quantity', '<', 50)->count(),
                'customers' => Customer::count(),
                'pending_merchants' => Merchant::where('is_approved', false)->count(),
                'scans_today' => QrScan::whereDate('scanned_at', today())->count(),
                'points_today' => (int) PointsTransaction::where('type', 'earn')->whereDate('transaction_date', today())->sum('amount'),
                'qr_active' => QrCode::where('status', 'active')->count(),
            ],
            'orderStatusCounts' => $orderStatusCounts,
            'ordersPerDay' => $ordersPerDay,
            'revenuePerDay' => $revenuePerDay,
            'scansPerDay' => $scansPerDay,
            'recentOrders' => Order::with('user')->latest()->take(8)->get(),
            'lowStock' => Product::with(['category', 'thumbnail'])->where('stock_quantity', '<', 50)->orderBy('stock_quantity')->take(6)->get(),
            'topProducts' => Product::with('thumbnail')->where('is_active', true)->orderByDesc('stock_quantity')->take(4)->get(),
            'pendingMerchants' => Merchant::with('user')->where('is_approved', false)->latest()->take(4)->get(),
        ]);
    }

    public function search(\Illuminate\Http\Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        return view('admin.search', [
            'q' => $q,
            'orders' => $q ? Order::where('order_number', 'like', "%{$q}%")->take(10)->get() : collect(),
            'customers' => $q ? Customer::with('user')->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('phone_number', 'like', "%{$q}%"))->take(10)->get() : collect(),
            'merchants' => $q ? Merchant::where('business_name', 'like', "%{$q}%")->orWhere('merchant_code', 'like', "%{$q}%")->take(10)->get() : collect(),
            'codes' => $q ? QrCode::where('serial_code', 'like', "%{$q}%")->orWhere('batch_id', 'like', "%{$q}%")->take(10)->get() : collect(),
            'products' => $q ? Product::where('name_ar', 'like', "%{$q}%")->orWhere('name_en', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")->take(10)->get() : collect(),
        ]);
    }
}
