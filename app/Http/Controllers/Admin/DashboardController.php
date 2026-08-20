<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Services\GeminiPerformanceSummary;
use App\Services\RiskAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GeminiPerformanceSummary $gemini, RiskAlertService $riskAlerts): View
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $ordersPerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'date' => $day->toDateString(),
            'value' => Order::whereDate('created_at', $day)->count(),
        ]);

        $revenuePerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'date' => $day->toDateString(),
            'value' => (float) Order::whereDate('created_at', $day)
                ->whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total_amount'),
        ]);

        $scansPerDay = $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'date' => $day->toDateString(),
            'value' => QrScan::whereDate('scanned_at', $day)->count(),
        ]);

        $orderStatusCounts = [
            'new' => Order::where('status', 'new')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
        ];

        $monthStart = now()->startOfMonth();
        $weekStart = Carbon::today()->subDays(6)->startOfDay();

        $stats = [
            'revenue' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])->sum('total_amount'),
            'revenue_today' => (float) Order::whereDate('created_at', today())
                ->whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total_amount'),
            'revenue_week' => (float) Order::where('created_at', '>=', $weekStart)
                ->whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total_amount'),
            'revenue_month' => (float) Order::where('created_at', '>=', $monthStart)
                ->whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total_amount'),
            'orders_total' => Order::count(),
            'orders_new' => $orderStatusCounts['new'],
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'orders_week' => Order::where('created_at', '>=', $weekStart)->count(),
            'orders_month' => Order::where('created_at', '>=', $monthStart)->count(),
            'products' => Product::count(),
            'low_stock' => Product::where('stock_quantity', '<', 50)->count(),
            'customers' => Customer::count(),
            'pending_merchants' => Merchant::where('is_approved', false)->count(),
            'scans_today' => QrScan::whereDate('scanned_at', today())->count(),
            'points_today' => (int) PointsTransaction::where('type', 'earn')->whereDate('transaction_date', today())->sum('amount'),
            'qr_active' => QrCode::where('status', 'active')->count(),
            'rewards_available' => CustomerReward::where('status', CustomerReward::STATUS_AVAILABLE)->count(),
            'rewards_used' => CustomerReward::where('status', CustomerReward::STATUS_USED)->count(),
            'rewards_today' => CustomerReward::whereDate('created_at', today())->count(),
            'technicians_total' => Customer::count(),
            'technicians_active' => Customer::whereHas('qrScans', fn ($q) => $q->where('scanned_at', '>=', now()->subDays(30)))->count(),
            'technicians_new_month' => Customer::where('created_at', '>=', $monthStart)->count(),
            'points_balance_total' => (int) Customer::sum('points_balance'),
            'points_earned_total' => (int) Customer::sum('total_points_earned'),
            'points_spent_total' => (int) Customer::sum('total_points_spent'),
            'points_earned_month' => (int) PointsTransaction::where('type', 'earn')->where('transaction_date', '>=', $monthStart)->sum('amount'),
            'points_spent_month' => (int) PointsTransaction::where('type', 'spend')->where('transaction_date', '>=', $monthStart)->sum('amount'),
            'qr_generated' => QrCode::where('status', 'active')->whereNull('printed_at')->whereNull('sold_at')->whereNull('used_at')->count(),
            'qr_printed' => QrCode::where('status', 'active')->whereNotNull('printed_at')->whereNull('sold_at')->whereNull('used_at')->count(),
            'qr_sold' => QrCode::where('status', 'active')->whereNotNull('sold_at')->whereNull('used_at')->count(),
            'qr_scanned' => QrCode::where(fn ($q) => $q->where('status', 'used')->orWhereNotNull('used_at'))->count(),
        ];

        $topTechnicians = Customer::with('user')
            ->withCount(['qrScans as scans_month' => fn ($q) => $q->where('scanned_at', '>=', $monthStart)])
            ->orderByDesc('scans_month')
            ->orderByDesc('total_points_earned')
            ->take(6)
            ->get();

        $metrics = [
            'generated_at' => now()->toDateTimeString(),
            'today' => [
                'orders' => $stats['orders_today'],
                'revenue' => $stats['revenue_today'],
                'scans' => $stats['scans_today'],
                'points_earned' => $stats['points_today'],
            ],
            'last_7_days' => [
                'orders' => $ordersPerDay->sum('value'),
                'revenue' => $revenuePerDay->sum('value'),
                'scans' => $scansPerDay->sum('value'),
                'orders_by_day' => $ordersPerDay->values()->all(),
                'revenue_by_day' => $revenuePerDay->values()->all(),
                'scans_by_day' => $scansPerDay->values()->all(),
                'from' => $weekStart->toDateString(),
                'to' => Carbon::today()->toDateString(),
            ],
            'pipeline' => $orderStatusCounts,
            'attention' => [
                'new_orders' => $stats['orders_new'],
                'processing_orders' => $orderStatusCounts['processing'],
                'pending_merchants' => $stats['pending_merchants'],
                'low_stock_products' => $stats['low_stock'],
            ],
            'totals' => [
                'orders' => $stats['orders_total'],
                'customers' => $stats['customers'],
                'products' => $stats['products'],
                'active_qr' => $stats['qr_active'],
                'lifetime_revenue' => (float) $stats['revenue'],
            ],
            'technicians' => [
                'total' => $stats['technicians_total'],
                'active_30d' => $stats['technicians_active'],
                'new_month' => $stats['technicians_new_month'],
                'points_balance' => $stats['points_balance_total'],
                'points_earned' => $stats['points_earned_total'],
                'points_spent' => $stats['points_spent_total'],
            ],
        ];

        $aiSummary = $gemini->generate(
            $metrics,
            'ar',
            $request->boolean('refresh_summary')
        );

        return view('admin.dashboard', [
            'stats' => $stats,
            'orderStatusCounts' => $orderStatusCounts,
            'ordersPerDay' => $ordersPerDay,
            'revenuePerDay' => $revenuePerDay,
            'scansPerDay' => $scansPerDay,
            'fraudAlerts' => $riskAlerts->summary(5),
            'aiSummary' => $aiSummary['text'],
            'aiSummaryOk' => $aiSummary['ok'],
            'topTechnicians' => $topTechnicians,
            'lowStock' => Product::with(['category', 'thumbnail'])->where('stock_quantity', '<', 50)->orderBy('stock_quantity')->take(6)->get(),
            'topProducts' => Product::with('thumbnail')->where('is_active', true)->orderByDesc('stock_quantity')->take(4)->get(),
            'pendingMerchants' => Merchant::with('user')->where('is_approved', false)->latest()->take(4)->get(),
        ]);
    }

    public function search(Request $request): View
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
