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
    $aiBlocks = preg_split("/\n{2,}/", trim((string) $aiSummary)) ?: [];
@endphp

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

{{-- Actions + order statuses in one calm panel --}}
<div class="ui-panel mt-5 !py-4">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-dark">{{ __('admin.home.qa_orders') }}</a>
        <a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_product') }}</a>
        <a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.add_category') }}</a>
        <a href="{{ route('admin.inventory.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.inventory') }}</a>
    </div>
    <div class="mt-4 grid gap-3 border-t border-[#E4E0D7] pt-4 sm:grid-cols-4">
        @foreach($orderStatusCounts as $st => $count)
            <a href="{{ route('admin.orders.index', ['view' => 'table', 'status' => $st]) }}"
               class="rounded-lg bg-[#F9F8F5] px-3 py-3 text-center transition hover:bg-[#F3F1EB]">
                <div class="text-lg font-bold text-maqam-ink">{{ $count }}</div>
                <div class="ui-muted">{{ $statusLabels[$st] }}</div>
            </a>
        @endforeach
    </div>
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

<div class="ui-panel mt-5">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="ui-section-title">{{ __('admin.home.loyalty_strip_title') }}</h3>
            <p class="ui-muted">{{ __('admin.home.loyalty_strip_hint') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.scans.index') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.scan_monitor') }}</a>
            <a href="{{ route('admin.loyalty.transactions') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.points_ledger') }}</a>
            <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.nav.generate_batch') }}</a>
        </div>
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
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
    </div>
</div>
@endsection
