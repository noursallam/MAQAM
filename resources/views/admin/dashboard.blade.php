@extends('admin.layouts.app')

@section('title', __('admin.home.title'))
@section('subtitle', __('admin.home.subtitle'))

@section('actions')
<a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.commerce.add_product') }}</a>
@endsection

@section('content')
@php
    $statusLabels = [
        'new' => __('admin.orders.new'),
        'processing' => __('admin.orders.processing'),
        'shipped' => __('admin.orders.shipped'),
        'delivered' => __('admin.orders.delivered'),
    ];
    $maxOrder = max(1, $ordersPerDay->max('value'));
    $maxRevenue = max(1, $revenuePerDay->max('value'));
    $maxScan = max(1, $scansPerDay->max('value'));
@endphp

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.orders.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.revenue') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ number_format($stats['revenue'], 0) }} <span class="text-base font-medium text-maqam-muted">ج.م</span></div>
        <div class="flex items-center gap-1.5 ui-kpi-hint">
            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <span>{{ $stats['orders_today'] }} {{ __('admin.home.orders_today') }}</span>
        </div>
    </a>
    <a href="{{ route('admin.orders.index', ['view' => 'kanban']) }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.new_orders') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['orders_new'] }}</div>
        <div class="flex items-center gap-1.5 mt-1 text-xs text-maqam-muted">
            <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <span>{{ __('admin.home.from_total') }} {{ $stats['orders_total'] }}</span>
        </div>
    </a>
    <a href="{{ route('admin.products.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.nav.products') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['products'] }}</div>
        <div class="flex items-center gap-1.5 mt-1 text-xs {{ $stats['low_stock'] ? 'text-amber-700' : 'text-maqam-muted' }}">
            @if($stats['low_stock'] > 0)
                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @endif
            <span>{{ $stats['low_stock'] }} {{ __('admin.home.low_stock') }}</span>
        </div>
    </a>
    <a href="{{ route('admin.customers.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.customers') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['customers'] }}</div>
        <div class="flex items-center gap-1.5 mt-1 text-xs text-maqam-muted">
            @if($stats['pending_merchants'] > 0)
                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @endif
            <span>{{ $stats['pending_merchants'] }} {{ __('admin.home.pending_merchants_short') }}</span>
        </div>
    </a>
</div>

<div class="ui-toolbar mt-5">
    <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-dark">{{ __('admin.home.qa_orders') }}</a>
    <a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_product') }}</a>
    <a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_category') }}</a>
    <a href="{{ route('admin.inventory.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.inventory') }}</a>
</div>

<div class="mt-2 grid gap-3 sm:grid-cols-4">
    @foreach($orderStatusCounts as $st => $count)
        <a href="{{ route('admin.orders.index', ['view' => 'table', 'status' => $st]) }}" class="ui-card-static px-4 py-3 text-center transition hover:border-maqam-gold">
            <div class="text-lg font-bold">{{ $count }}</div>
            <div class="ui-muted">{{ $statusLabels[$st] }}</div>
        </a>
    @endforeach
</div>

<div class="mt-8 grid gap-5 xl:grid-cols-5">
    <div class="ui-panel xl:col-span-3">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="ui-section-title">{{ __('admin.home.recent_orders') }}</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
        </div>
        <div class="space-y-3">
            @forelse($recentOrders as $order)
                @php
                    $statusColors = [
                        'new' => 'bg-blue-100 text-blue-700 border-blue-200',
                        'processing' => 'bg-amber-100 text-amber-700 border-amber-200',
                        'shipped' => 'bg-purple-100 text-purple-700 border-purple-200',
                        'delivered' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    ];
                    $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                @endphp
                <a href="{{ route('admin.orders.show', $order) }}" class="group relative overflow-hidden rounded-xl border border-[#D8D4CB] bg-white p-4 transition-all hover:border-maqam-gold hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#EAE7DF]">
                                <svg class="h-5 w-5 text-maqam-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-maqam-ink">{{ $order->order_number }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                        {{ $statusLabels[$order->status] ?? $order->status }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-xs text-maqam-muted">
                                    <span class="truncate">{{ $order->user?->full_name }}</span>
                                    <span class="text-[#D8D4CB]">·</span>
                                    <span>{{ strtoupper($order->payment_method) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-end">
                            <div class="text-lg font-bold text-maqam-ink">{{ number_format($order->total_amount, 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
                            <div class="mt-1 text-xs text-maqam-muted">{{ $order->created_at?->diffForHumans() ?? '-' }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[#EAE7DF]">
                        <svg class="h-8 w-8 text-maqam-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <p class="ui-muted">{{ __('admin.orders.empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="space-y-5 xl:col-span-2">
        <div class="ui-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="ui-section-title">{{ __('admin.home.top_products') }}</h2>
                <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @forelse($topProducts as $product)
                    <a href="{{ route('admin.products.edit', $product) }}" class="group">
                        <div class="aspect-square overflow-hidden rounded-lg border border-[#D8D4CB] bg-[#EAE7DF]">
                            @if($product->thumbnailUrl())
                                <img src="{{ $product->thumbnailUrl() }}" alt="{{ $product->name_ar ?: $product->name_en }}" class="h-full w-full object-cover transition-transform group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center text-maqam-muted">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="mt-2 truncate text-sm font-medium">{{ $product->name_ar ?: $product->name_en }}</div>
                        <div class="ui-muted text-xs">{{ number_format($product->price, 0) }} ج.م</div>
                    </a>
                @empty
                    <p class="ui-muted col-span-2 py-8 text-center">{{ __('admin.products.empty') }}</p>
                @endforelse
            </div>
        </div>

        <div class="ui-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="ui-section-title">{{ __('admin.home.low_stock') }}</h2>
                <a href="{{ route('admin.inventory.index') }}" class="text-sm font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
            </div>
            @forelse($lowStock as $product)
                <a href="{{ route('admin.products.edit', $product) }}" class="ui-row mb-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-[#D8D4CB] bg-[#EAE7DF]">
                            @if($product->thumbnailUrl())
                                <img src="{{ $product->thumbnailUrl() }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $product->name_ar ?: $product->name_en }}</div>
                            <div class="ui-muted">{{ $product->category?->name_ar }}</div>
                        </div>
                    </div>
                    <span class="font-bold text-amber-700">{{ $product->stock_quantity }}</span>
                </a>
            @empty
                <p class="ui-muted">{{ __('admin.home.stock_ok') }}</p>
            @endforelse
        </div>

        @if($pendingMerchants->isNotEmpty())
            <div class="ui-panel">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="ui-section-title">{{ __('admin.nav.merchant_inbox') }}</h2>
                    <a href="{{ route('admin.merchants.inbox') }}" class="text-xs font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
                </div>
                @foreach($pendingMerchants as $m)
                    <a href="{{ route('admin.merchants.inbox', ['review' => $m->id]) }}" class="ui-row mb-2 text-sm">
                        <div>
                            <div class="font-medium">{{ $m->business_name }}</div>
                            <div class="ui-muted">{{ $m->user?->phone_number }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <div class="ui-panel">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="ui-section-title">{{ __('admin.home.chart_orders') }}</h3>
            <div class="flex items-center gap-1 text-xs text-maqam-muted">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>Last 7 days</span>
            </div>
        </div>
        <div class="flex h-40 items-end gap-2">
            @foreach($ordersPerDay as $point)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="w-full rounded-t-lg bg-maqam-navy/90 transition-all hover:bg-maqam-navy" style="height: {{ max(8, ($point['value'] / $maxOrder) * 100) }}%"></div>
                    <span class="text-[10px] font-medium text-maqam-muted">{{ $point['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    <div class="ui-panel">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="ui-section-title">{{ __('admin.home.chart_revenue') }}</h3>
            <div class="flex items-center gap-1 text-xs text-maqam-muted">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>Last 7 days</span>
            </div>
        </div>
        <div class="flex h-40 items-end gap-2">
            @foreach($revenuePerDay as $point)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="w-full rounded-t-lg bg-maqam-gold transition-all hover:bg-maqam-gold-dark" style="height: {{ max(8, ($point['value'] / $maxRevenue) * 100) }}%"></div>
                    <span class="text-[10px] font-medium text-maqam-muted">{{ $point['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="ui-panel mt-6 border-dashed">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="ui-section-title text-maqam-muted">{{ __('admin.home.loyalty_strip_title') }}</h3>
            <p class="ui-muted">{{ __('admin.home.loyalty_strip_hint') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.scans.index') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.scan_monitor') }}</a>
            <a href="{{ route('admin.loyalty.transactions') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.points_ledger') }}</a>
            <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.generate_batch') }}</a>
        </div>
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="ui-card-soft px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.scans_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ $stats['scans_today'] }}</div>
        </div>
        <div class="ui-card-soft px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.points_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['points_today']) }}</div>
        </div>
        <div class="ui-card-soft px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.active_qr') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_active']) }}</div>
        </div>
    </div>
</div>
@endsection
