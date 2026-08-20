@extends('admin.layouts.app')

@section('title', __('admin.home.title'))
@section('subtitle', __('admin.home.subtitle'))

@section('actions')
@if(\App\Support\AdminAccess::can(auth()->user(), 'commerce'))
<a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.commerce.add_product') }}</a>
@endif
@endsection

@section('content')
@php
    $statusLabels = [
        'new' => __('admin.orders.new'),
        'processing' => __('admin.orders.processing'),
        'shipped' => __('admin.orders.shipped'),
        'delivered' => __('admin.orders.delivered'),
    ];
    $aiBlocks = preg_split("/\n{2,}/", trim((string) $aiSummary)) ?: [];
    $maxOrders = max(1, (int) $ordersPerDay->max('value'));
    $maxRevenue = max(1, (float) $revenuePerDay->max('value'));
    $maxScans = max(1, (int) $scansPerDay->max('value'));
    $fraudHot = ($fraudAlerts['total'] ?? 0) > 0;
@endphp

@if(\App\Support\AdminAccess::can(auth()->user(), 'risk'))
    <a href="{{ route('admin.risk.index') }}"
       class="mb-5 block overflow-hidden rounded-2xl border {{ $fraudHot ? 'border-red-300 bg-red-50 shadow-[0_0_0_1px_rgba(220,38,38,0.08)]' : 'border-[#E4E0D7] bg-white' }} transition hover:opacity-95">
        <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 {{ $fraudHot ? 'bg-red-600 text-white' : 'bg-[#F9F8F5] text-maqam-ink' }}">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $fraudHot ? 'bg-white/15' : 'bg-white border border-[#E4E0D7]' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold">{{ __('admin.home.fraud_widget_title') }}</div>
                    <div class="text-xs {{ $fraudHot ? 'text-white/85' : 'text-maqam-muted' }}">
                        {{ $fraudHot ? __('admin.home.fraud_widget_alert', ['count' => $fraudAlerts['total']]) : __('admin.home.fraud_widget_clear') }}
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full px-2.5 py-1 {{ $fraudHot ? 'bg-white/15' : 'bg-white border border-[#E4E0D7]' }}">{{ __('admin.home.high_risk') }}: {{ $fraudAlerts['frozen'] }}</span>
                <span class="rounded-full px-2.5 py-1 {{ $fraudHot ? 'bg-white/15' : 'bg-white border border-[#E4E0D7]' }}">{{ __('admin.risk.geo_velocity') }}: {{ $fraudAlerts['geo'] }}</span>
                <span class="rounded-full px-2.5 py-1 {{ $fraudHot ? 'bg-white/15' : 'bg-white border border-[#E4E0D7]' }}">{{ __('admin.home.failed_sync') }}: {{ $fraudAlerts['failed_sync'] }}</span>
            </div>
        </div>
        @if($fraudHot && !empty($fraudAlerts['items']))
            <div class="space-y-2 px-5 py-4">
                @foreach($fraudAlerts['items'] as $item)
                    <div class="flex items-start justify-between gap-3 rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <div class="font-medium text-red-900">{{ $item['title'] }}</div>
                            <div class="mt-0.5 text-xs text-red-700/80">{{ $item['detail'] }}</div>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $item['severity'] === 'high' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">
                            {{ __('admin.risk.'.$item['severity']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </a>
@endif

{{-- Period glance --}}
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-[#E4E0D7] bg-white px-4 py-3">
        <div class="ui-muted text-xs">{{ __('admin.home.period_today') }}</div>
        <div class="mt-1 text-xl font-bold">{{ number_format($stats['revenue_today'], 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
        <div class="mt-1 text-xs text-maqam-muted">{{ $stats['orders_today'] }} {{ __('admin.home.orders_today') }}</div>
    </div>
    <div class="rounded-xl border border-[#E4E0D7] bg-white px-4 py-3">
        <div class="ui-muted text-xs">{{ __('admin.home.period_week') }}</div>
        <div class="mt-1 text-xl font-bold">{{ number_format($stats['revenue_week'], 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
        <div class="mt-1 text-xs text-maqam-muted">{{ $stats['orders_week'] }} {{ __('admin.home.orders_7d') }}</div>
    </div>
    <div class="rounded-xl border border-[#E4E0D7] bg-white px-4 py-3">
        <div class="ui-muted text-xs">{{ __('admin.home.period_month') }}</div>
        <div class="mt-1 text-xl font-bold">{{ number_format($stats['revenue_month'], 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
        <div class="mt-1 text-xs text-maqam-muted">{{ $stats['orders_month'] }} {{ __('admin.home.orders_month') }}</div>
    </div>
</div>

{{-- KPI row --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.orders.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.revenue') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EAE7DF] text-maqam-navy">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ number_format($stats['revenue'], 0) }} <span class="text-base font-medium text-maqam-muted">ج.م</span></div>
        <div class="ui-kpi-hint">{{ $stats['orders_today'] }} {{ __('admin.home.orders_today') }}</div>
    </a>
    <a href="{{ route('admin.orders.index', ['view' => 'kanban']) }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.new_orders') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EAE7DF] text-maqam-navy">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['orders_new'] }}</div>
        <div class="mt-1 text-xs text-maqam-muted">{{ __('admin.home.from_total') }} {{ $stats['orders_total'] }}</div>
    </a>
    <a href="{{ route('admin.products.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.nav.products') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EAE7DF] text-maqam-navy">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['products'] }}</div>
        <div class="mt-1 text-xs {{ $stats['low_stock'] ? 'text-amber-700' : 'text-maqam-muted' }}">
            {{ $stats['low_stock'] }} {{ __('admin.home.low_stock') }}
        </div>
    </a>
    <a href="{{ route('admin.customers.index') }}" class="ui-kpi group">
        <div class="flex items-start justify-between">
            <div class="ui-kpi-label">{{ __('admin.home.customers') }}</div>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EAE7DF] text-maqam-navy">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <div class="ui-kpi-value">{{ $stats['customers'] }}</div>
        <div class="mt-1 text-xs text-maqam-muted">
            {{ $stats['pending_merchants'] }} {{ __('admin.home.pending_merchants_short') }}
        </div>
    </a>
</div>

{{-- 7-day charts --}}
@php
    $chartRows = $ordersPerDay->values()->map(function ($orderPoint, $i) use ($revenuePerDay, $scansPerDay) {
        return [
            'label' => $orderPoint['label'],
            'orders' => (int) $orderPoint['value'],
            'revenue' => (float) ($revenuePerDay->values()[$i]['value'] ?? 0),
            'scans' => (int) ($scansPerDay->values()[$i]['value'] ?? 0),
        ];
    });
    $chartHasData = $chartRows->sum(fn ($r) => $r['orders'] + $r['revenue'] + $r['scans']) > 0;
    $chartH = 200;
    $chartPadTop = 16;
    $chartPadBottom = 36;
    $plotH = $chartH - $chartPadTop - $chartPadBottom;
    $dayCount = max(1, $chartRows->count());
    $groupW = 100 / $dayCount;
@endphp
<div class="ui-panel mt-5">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="ui-section-title">{{ __('admin.home.chart_last_7_days') }}</h2>
            <p class="ui-muted text-sm">{{ __('admin.home.chart_overview_hint') }}</p>
        </div>
        <div class="flex flex-wrap gap-4 text-xs font-medium">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#1B2A4A"></span>{{ __('admin.home.chart_legend_orders') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#C4A574"></span>{{ __('admin.home.chart_legend_revenue') }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" style="background:#059669"></span>{{ __('admin.home.chart_legend_scans') }}</span>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-[#E8E4DA] bg-white p-3 sm:p-4">
        @if(! $chartHasData)
            <div class="flex min-h-[220px] flex-col items-center justify-center gap-2 py-10 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F3F1EB] text-maqam-muted">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 16l4-5 3 3 5-7"/></svg>
                </div>
                <p class="font-medium text-maqam-ink">{{ __('admin.home.chart_no_data') }}</p>
                <p class="max-w-sm text-sm text-maqam-muted">{{ __('admin.home.chart_no_data_hint') }}</p>
            </div>
        @else
            <svg viewBox="0 0 700 {{ $chartH }}" class="h-auto w-full" style="min-height:220px" role="img" aria-label="{{ __('admin.home.chart_last_7_days') }}" preserveAspectRatio="xMidYMid meet">
                {{-- grid --}}
                @for($g = 0; $g <= 4; $g++)
                    @php $gy = $chartPadTop + ($plotH * $g / 4); @endphp
                    <line x1="0" y1="{{ $gy }}" x2="700" y2="{{ $gy }}" stroke="#EDEAE3" stroke-width="1"/>
                @endfor
                <line x1="0" y1="{{ $chartPadTop + $plotH }}" x2="700" y2="{{ $chartPadTop + $plotH }}" stroke="#D8D4CB" stroke-width="1.5"/>

                @foreach($chartRows as $i => $row)
                    @php
                        $cx = (($i + 0.5) * $groupW / 100) * 700;
                        $barW = 14;
                        $gap = 4;
                        $hO = $row['orders'] > 0 ? max(8, ($row['orders'] / $maxOrders) * $plotH) : 0;
                        $hR = $row['revenue'] > 0 ? max(8, ($row['revenue'] / $maxRevenue) * $plotH) : 0;
                        $hS = $row['scans'] > 0 ? max(8, ($row['scans'] / $maxScans) * $plotH) : 0;
                        $base = $chartPadTop + $plotH;
                        $x0 = $cx - ($barW * 1.5) - $gap;
                    @endphp
                    <rect x="{{ $x0 }}" y="{{ $base - $hO }}" width="{{ $barW }}" height="{{ $hO }}" rx="3" fill="#1B2A4A">
                        <title>{{ $row['label'] }} — {{ __('admin.home.chart_legend_orders') }}: {{ $row['orders'] }}</title>
                    </rect>
                    <rect x="{{ $x0 + $barW + $gap }}" y="{{ $base - $hR }}" width="{{ $barW }}" height="{{ $hR }}" rx="3" fill="#C4A574">
                        <title>{{ $row['label'] }} — {{ __('admin.home.chart_legend_revenue') }}: {{ number_format($row['revenue'], 0) }}</title>
                    </rect>
                    <rect x="{{ $x0 + ($barW + $gap) * 2 }}" y="{{ $base - $hS }}" width="{{ $barW }}" height="{{ $hS }}" rx="3" fill="#059669">
                        <title>{{ $row['label'] }} — {{ __('admin.home.chart_legend_scans') }}: {{ $row['scans'] }}</title>
                    </rect>
                    <text x="{{ $cx }}" y="{{ $chartH - 12 }}" text-anchor="middle" fill="#7A756A" font-size="12" font-family="Alexandria, sans-serif">{{ $row['label'] }}</text>
                @endforeach
            </svg>
        @endif
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-[#E8E4DA] bg-[#F9F8F5] px-4 py-3 text-center sm:text-start">
            <div class="text-xs text-maqam-muted">{{ __('admin.home.chart_legend_orders') }} · {{ __('admin.home.chart_last_7_days') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight">{{ (int) $ordersPerDay->sum('value') }}</div>
        </div>
        <div class="rounded-xl border border-[#E8E4DA] bg-[#F9F8F5] px-4 py-3 text-center sm:text-start">
            <div class="text-xs text-maqam-muted">{{ __('admin.home.chart_legend_revenue') }} · {{ __('admin.home.chart_last_7_days') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight">{{ number_format($revenuePerDay->sum('value'), 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
        </div>
        <div class="rounded-xl border border-[#E8E4DA] bg-[#F9F8F5] px-4 py-3 text-center sm:text-start">
            <div class="text-xs text-maqam-muted">{{ __('admin.home.chart_legend_scans') }} · {{ __('admin.home.chart_last_7_days') }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight">{{ (int) $scansPerDay->sum('value') }}</div>
        </div>
    </div>
</div>

{{-- Actions + order statuses in one calm panel --}}
<div class="ui-panel mt-5 !py-4">
    <div class="flex flex-wrap items-center gap-2">
        @if(\App\Support\AdminAccess::can(auth()->user(), 'orders'))
            <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-dark">{{ __('admin.home.qa_orders') }}</a>
        @endif
        @if(\App\Support\AdminAccess::can(auth()->user(), 'commerce'))
            <a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_product') }}</a>
            <a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_category') }}</a>
            <a href="{{ route('admin.inventory.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.inventory') }}</a>
        @endif
    </div>
    @if(\App\Support\AdminAccess::can(auth()->user(), 'orders'))
    <div class="mt-4 grid gap-3 border-t border-[#E4E0D7] pt-4 sm:grid-cols-4">
        @foreach($orderStatusCounts as $st => $count)
            <a href="{{ route('admin.orders.index', ['view' => 'table', 'status' => $st]) }}"
               class="rounded-lg bg-[#F9F8F5] px-3 py-3 text-center transition hover:bg-[#F3F1EB]">
                <div class="text-lg font-bold text-maqam-ink">{{ $count }}</div>
                <div class="ui-muted">{{ $statusLabels[$st] }}</div>
            </a>
        @endforeach
    </div>
    @endif
</div>

{{-- Gemini summary: light card (no heavy navy strip) --}}
<div class="ui-panel mt-5 !p-0 overflow-hidden" x-data="{ open: false }">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
         :class="open ? 'border-b border-[#E4E0D7]' : ''">
        <button type="button" @click="open = !open" class="flex min-w-0 flex-1 items-center gap-3 text-start">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#EAE7DF] text-maqam-gold-dark">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2l1.2 4.2L17.5 7.5l-4.3 1.3L12 13l-1.2-4.2L6.5 7.5l4.3-1.3L12 2zm7 9l.8 2.8L23 15l-3.2.9L19 19l-.8-3.1L15 15l3.2-.9L19 11zM5 14l.7 2.4L8 17l-2.3.7L5 20l-.7-2.3L2 17l2.3-.6L5 14z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="ui-section-title">{{ __('admin.home.chart_overview') }}</h2>
                    <span class="rounded-md bg-[#EAE7DF] px-2 py-0.5 text-[10px] font-semibold text-maqam-gold-dark">Gemini AI</span>
                </div>
                <p class="mt-0.5 text-xs text-maqam-muted">{{ __('admin.home.ai_summary_hint') }}</p>
            </div>
            <svg class="h-4 w-4 shrink-0 text-maqam-muted transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div class="flex items-center gap-2">
            <button type="button" @click="open = !open" class="ui-btn ui-btn-ghost text-xs !py-1.5">
                <span x-text="open ? '{{ __('admin.home.ai_summary_collapse') }}' : '{{ __('admin.home.ai_summary_expand') }}'"></span>
            </button>
            <a href="{{ route('admin.dashboard', ['refresh_summary' => 1]) }}"
               class="ui-btn ui-btn-ghost text-xs !py-1.5"
               onclick="return confirm(@json(__('admin.home.ai_summary_refresh_confirm')))">
                {{ __('admin.home.ai_summary_refresh') }}
            </a>
        </div>
    </div>

    <div x-show="open" x-collapse x-cloak class="bg-[#FBFBF9] px-5 py-5">
        @if(! ($aiSummaryOk ?? false))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-7 text-amber-900">
                {{ $aiSummary }}
            </div>
        @else
            <div class="space-y-3">
                @foreach($aiBlocks as $block)
                    @php
                        $lines = preg_split("/\r\n|\n|\r/", trim($block)) ?: [];
                        $first = $lines[0] ?? '';
                        $isTitle = (bool) preg_match('/^(?:\d+\)\s*)?(نظرة عامة|أداء آخر|نقاط تحتاج|توصيات)/u', $first);
                        $title = $isTitle ? $first : null;
                        $body = $isTitle ? trim(implode("\n", array_slice($lines, 1))) : trim($block);
                    @endphp
                    <div class="rounded-lg border border-[#E4E0D7] bg-white px-4 py-3">
                        @if($title)
                            <div class="mb-1.5 text-xs font-semibold text-maqam-gold-dark">{{ $title }}</div>
                        @endif
                        <div class="whitespace-pre-line text-sm leading-7 text-maqam-ink/90">{{ $body !== '' ? $body : $first }}</div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 text-[11px] text-maqam-muted">{{ __('admin.home.ai_summary_powered') }}</div>
        @endif
    </div>
</div>

{{-- Bottom widgets --}}
<div class="mt-5 grid items-start gap-5 lg:grid-cols-3">
    <div class="ui-panel">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="ui-section-title">{{ __('admin.home.top_products') }}</h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @forelse($topProducts as $product)
                <a href="{{ route('admin.products.edit', $product) }}" class="group">
                    <div class="aspect-square overflow-hidden rounded-lg border border-[#E4E0D7] bg-[#EAE7DF]">
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
                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-[#E4E0D7] bg-[#EAE7DF]">
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

{{-- Technicians & QR lifecycle --}}
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    <div class="ui-panel">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h3 class="ui-section-title">{{ __('admin.home.technicians_title') }}</h3>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.customers') }}</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.technicians_total') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['technicians_total']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.technicians_active') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['technicians_active']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.technicians_new_month') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['technicians_new_month']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.points_balance_total') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['points_balance_total']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.points_earned_month') }}</div>
                <div class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($stats['points_earned_month']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.home.points_spent_month') }}</div>
                <div class="mt-1 text-xl font-bold text-amber-700">{{ number_format($stats['points_spent_month']) }}</div>
            </div>
        </div>
        <div class="mt-4 border-t border-[#E4E0D7] pt-4">
            <div class="mb-2 text-sm font-medium">{{ __('admin.home.top_technicians') }}</div>
            <div class="space-y-2">
                @forelse($topTechnicians as $tech)
                    <a href="{{ route('admin.customers.show', $tech) }}" class="flex items-center justify-between rounded-lg bg-[#F9F8F5] px-3 py-2 text-sm transition hover:bg-[#F3F1EB]">
                        <span class="font-medium">{{ $tech->user?->full_name ?: '—' }}</span>
                        <span class="ui-muted">{{ $tech->scans_month }} {{ __('admin.home.scans_month') }} · {{ number_format($tech->points_balance) }} pts</span>
                    </a>
                @empty
                    <div class="ui-muted text-sm">{{ __('admin.empty_title') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="ui-panel">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="ui-section-title">{{ __('admin.home.qr_lifecycle') }}</h3>
            <a href="{{ route('admin.qr-codes.tracker') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.qr_tracker') }}</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.qr.life_generated') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_generated']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.qr.life_printed') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_printed']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.qr.life_sold') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_sold']) }}</div>
            </div>
            <div class="rounded-lg bg-[#F9F8F5] px-3 py-3">
                <div class="ui-muted text-xs">{{ __('admin.qr.life_scanned') }}</div>
                <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_scanned']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="ui-panel mt-5">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="ui-section-title">{{ __('admin.home.loyalty_strip_title') }}</h3>
            <p class="ui-muted">{{ __('admin.home.loyalty_strip_hint') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.rewards.index') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.rewards') }}</a>
            <a href="{{ route('admin.scans.index') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.scan_monitor') }}</a>
            <a href="{{ route('admin.loyalty.transactions') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.points_ledger') }}</a>
            <a href="{{ route('admin.loyalty.spins') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.lucky_wheel') }}</a>
        </div>
    </div>
    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-lg bg-[#F9F8F5] px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.scans_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ $stats['scans_today'] }}</div>
        </div>
        <div class="rounded-lg bg-[#F9F8F5] px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.points_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['points_today']) }}</div>
        </div>
        <div class="rounded-lg bg-[#F9F8F5] px-4 py-3">
            <div class="ui-muted">{{ __('admin.home.active_qr') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['qr_active']) }}</div>
        </div>
        <a href="{{ route('admin.rewards.index', ['status' => 'available']) }}" class="rounded-lg bg-[#F9F8F5] px-4 py-3 transition hover:bg-[#F3F1EB]">
            <div class="ui-muted">{{ __('admin.home.rewards_available') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['rewards_available']) }}</div>
        </a>
        <a href="{{ route('admin.rewards.index', ['status' => 'used']) }}" class="rounded-lg bg-[#F9F8F5] px-4 py-3 transition hover:bg-[#F3F1EB]">
            <div class="ui-muted">{{ __('admin.home.rewards_used') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['rewards_used']) }}</div>
        </a>
        <a href="{{ route('admin.rewards.index') }}" class="rounded-lg bg-[#F9F8F5] px-4 py-3 transition hover:bg-[#F3F1EB]">
            <div class="ui-muted">{{ __('admin.home.rewards_today') }}</div>
            <div class="mt-1 text-xl font-bold">{{ number_format($stats['rewards_today']) }}</div>
        </a>
    </div>
</div>
@endsection
