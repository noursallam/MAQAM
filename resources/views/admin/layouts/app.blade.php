<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.home.title')) — MAQAM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:ital,opsz,wght@0,14..32,300..700;1,14..32,300..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $pendingMerchants = \App\Models\Merchant::where('is_approved', false)->count();
    $newOrders = \App\Models\Order::where('status', 'new')->count();
    $riskAlertCount = 0;
    if (\App\Support\AdminAccess::can(auth()->user(), 'risk')) {
        $riskAlertCount = (int) \App\Models\User::where('role', 'customer')->where('is_active', false)->count()
            + (int) \App\Models\QrScan::where('sync_status', 'failed')->count();
    }
    $badgeTotal = $pendingMerchants + $newOrders + $riskAlertCount;
    $can = fn (string $module) => \App\Support\AdminAccess::can(auth()->user(), $module);

    $navGroups = [
        [
            'key' => 'store_ops',
            'label' => __('admin.nav.store_ops'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
            'items' => [
                ['module' => 'orders', 'route' => 'admin.orders.index', 'label' => __('admin.nav.orders_pipeline'), 'match' => 'admin.orders.*', 'badge' => $newOrders ?: null],
                ['module' => 'commerce', 'route' => 'admin.categories.index', 'label' => __('admin.nav.store_categories'), 'match' => 'admin.categories.*', 'badge' => null],
                ['module' => 'commerce', 'route' => 'admin.products.index', 'label' => __('admin.nav.products'), 'match' => 'admin.products.*', 'badge' => null],
                ['module' => 'commerce', 'route' => 'admin.inventory.index', 'label' => __('admin.nav.inventory'), 'match' => 'admin.inventory.*', 'badge' => null],
                ['module' => 'commerce', 'route' => 'admin.banners.index', 'label' => __('admin.nav.banners'), 'match' => 'admin.banners.*', 'badge' => null],
            ],
        ],
        [
            'key' => 'people',
            'label' => __('admin.nav.people'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'items' => [
                ['module' => 'customers', 'route' => 'admin.customers.index', 'label' => __('admin.nav.customers'), 'match' => 'admin.customers.*', 'badge' => null],
                ['module' => 'customers', 'route' => 'admin.audience.index', 'label' => __('admin.nav.audience'), 'match' => 'admin.audience.*', 'badge' => null],
                ['module' => 'merchants', 'route' => 'admin.merchants.index', 'label' => __('admin.nav.merchants'), 'match' => 'admin.merchants.index', 'badge' => null, 'also' => ['admin.merchants.create', 'admin.merchants.edit', 'admin.merchants.store', 'admin.merchants.update']],
                ['module' => 'merchants', 'route' => 'admin.merchants.inbox', 'label' => __('admin.nav.merchant_inbox'), 'match' => 'admin.merchants.inbox', 'badge' => $pendingMerchants ?: null],
            ],
        ],
        [
            'key' => 'loyalty',
            'label' => __('admin.nav.loyalty'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 13C10.832 21 4 17.5 4 11V7l8-3 8 3v4c0 6.5-6.832 10-8 10z"/></svg>',
            'items' => [
                ['module' => 'loyalty', 'route' => 'admin.ranks.index', 'label' => __('admin.nav.ranks'), 'match' => 'admin.ranks.*', 'badge' => null],
                ['module' => 'loyalty', 'route' => 'admin.loyalty.transactions', 'label' => __('admin.nav.points_ledger'), 'match' => 'admin.loyalty.transactions', 'badge' => null],
                ['module' => 'loyalty', 'route' => 'admin.loyalty.spins', 'label' => __('admin.nav.lucky_wheel'), 'match' => 'admin.loyalty.spins', 'badge' => null],
                ['module' => 'loyalty', 'route' => 'admin.rewards.index', 'label' => __('admin.nav.rewards'), 'match' => 'admin.rewards.*', 'badge' => null],
                ['module' => 'coupons', 'route' => 'admin.coupons.index', 'label' => __('admin.nav.coupons'), 'match' => 'admin.coupons.*', 'badge' => null],
                ['module' => 'qr', 'route' => 'admin.prize-categories.index', 'label' => __('admin.nav.prize_categories'), 'match' => 'admin.prize-categories.*', 'badge' => null],
                ['module' => 'qr', 'route' => 'admin.qr-codes.create', 'label' => __('admin.nav.generate_batch'), 'match' => 'admin.qr-codes.create', 'badge' => null],
                ['module' => 'qr', 'route' => 'admin.qr-codes.index', 'label' => __('admin.nav.batches'), 'match' => 'admin.qr-codes.index', 'badge' => null],
                ['module' => 'qr', 'route' => 'admin.qr-codes.tracker', 'label' => __('admin.nav.qr_tracker'), 'match' => 'admin.qr-codes.tracker', 'badge' => null],
                ['module' => 'qr', 'route' => 'admin.scans.index', 'label' => __('admin.nav.scan_monitor'), 'match' => 'admin.scans.*', 'badge' => null],
            ],
        ],
        [
            'key' => 'communications',
            'label' => __('admin.nav.communications'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
            'items' => [
                ['module' => 'notifications', 'route' => 'admin.notifications.create', 'label' => __('admin.nav.notifications_composer'), 'match' => 'admin.notifications.create', 'badge' => null],
                ['module' => 'notifications', 'route' => 'admin.notifications.index', 'label' => __('admin.nav.notifications_history'), 'match' => 'admin.notifications.index', 'badge' => null],
                ['module' => 'notifications', 'route' => 'admin.seminars.index', 'label' => __('admin.nav.seminars'), 'match' => 'admin.seminars.*', 'badge' => null],
                ['module' => 'notifications', 'route' => 'admin.links.index', 'label' => __('admin.nav.links_page'), 'match' => 'admin.links.*', 'badge' => null],
            ],
        ],
        [
            'key' => 'system',
            'label' => __('admin.nav.system'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'items' => [
                ['module' => 'risk', 'route' => 'admin.risk.index', 'label' => __('admin.nav.risk_desk'), 'match' => 'admin.risk.*', 'badge' => $riskAlertCount ?: null],
                ['module' => 'settings', 'route' => 'admin.settings.index', 'label' => __('admin.nav.settings'), 'match' => 'admin.settings.*', 'badge' => null],
                ['module' => 'settings', 'route' => 'admin.app-texts.index', 'label' => __('admin.nav.app_texts'), 'match' => 'admin.app-texts.*', 'badge' => null],
                ['module' => 'settings', 'route' => 'admin.whatsapp.index', 'label' => __('admin.nav.whatsapp'), 'match' => 'admin.whatsapp.*', 'badge' => null],
                ['module' => 'admins', 'route' => 'admin.admins.index', 'label' => __('admin.nav.admins_rbac'), 'match' => 'admin.admins.*', 'badge' => null, 'also' => ['admin.admin-roles.*']],
                ['module' => 'api_docs', 'route' => 'admin.api-docs.index', 'label' => __('admin.nav.api_docs'), 'match' => 'admin.api-docs.*', 'badge' => null],
            ],
        ],
    ];

    $navGroups = collect($navGroups)->map(function ($group) use ($can) {
        $group['items'] = array_values(array_filter(
            $group['items'],
            fn ($item) => $can($item['module'] ?? 'dashboard')
        ));

        return $group;
    })->filter(fn ($group) => count($group['items']) > 0)->values()->all();

    $activeGroupKey = '';
    foreach ($navGroups as $idx => $group) {
        foreach ($group['items'] as $item) {
            if (request()->routeIs($item['match']) || (!empty($item['also']) && request()->routeIs(...$item['also']))) {
                $activeGroupKey = (string) $idx;
                break 2;
            }
        }
    }
    $autoExpandGroupKey = ($activeGroupKey !== '' && ! request()->routeIs('admin.dashboard')) ? $activeGroupKey : '';
@endphp
<body class="min-h-screen bg-maqam-bg text-maqam-ink antialiased" style="font-family:'IBM Plex Sans Arabic', Inter, 'DM Sans', ui-sans-serif, system-ui, sans-serif">
<div class="flex min-h-screen">
    {{-- Sidebar: light, quiet, grouped (inspired by modern store panels) --}}
    <aside class="fixed inset-y-0 {{ $isRtl ? 'right-0 border-l' : 'left-0 border-r' }} z-30 flex w-72 flex-col border-[#ECECEF] bg-maqam-sidebar">
        <div class="flex items-center gap-2.5 px-5 pb-4 pt-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-maqam-gold text-sm font-bold text-maqam-navy shadow-sm">M</span>
            <span class="text-lg font-bold tracking-[0.18em] text-maqam-ink">MAQAM</span>
        </div>

        <div class="px-4">
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-xl bg-[#EEEEF0] px-3 py-2.5 transition hover:bg-[#E8E8EB]">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-maqam-gold-dark shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 9l1.5-5h15L21 9M3 9h18M3 9v11h18V9M9 20v-6h6v6"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ __('admin.store_console') }}</span>
                    <span class="block truncate text-[11px] text-maqam-muted">{{ __('admin.admin_panel') }}</span>
                </span>
                <svg class="h-4 w-4 text-maqam-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 9l4-4 4 4M16 15l-4 4-4-4"/></svg>
            </a>
        </div>

        <nav class="sidebar-scroll mt-4 flex-1 overflow-y-auto border-t border-[#ECECEF] px-4 py-4" x-data="sidebarNav()">
            @php $dashboardActive = request()->routeIs('admin.dashboard'); @endphp
            <a href="{{ route('admin.dashboard') }}" class="ui-nav-item {{ $dashboardActive ? 'is-active' : '' }}">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 5a1 1 0 011-1h5v7H4V5zm10-1h5a1 1 0 011 1v4h-6V4zm0 9h6v6a1 1 0 01-1 1h-5v-7zM4 15h6v5H5a1 1 0 01-1-1v-4z"/></svg>
                <span>{{ __('admin.nav.command_center') }}</span>
            </a>

            <div class="my-3 border-t border-[#ECECEF]"></div>

            <div class="space-y-1">
                @foreach($navGroups as $groupKey => $group)
                    @php
                        $groupBadge = collect($group['items'])->sum(fn ($item) => (int) ($item['badge'] ?? 0));
                        $groupActive = (string) $groupKey === $activeGroupKey;
                    @endphp
                    <div>
                        <button type="button" @click="toggle('{{ $groupKey }}')"
                                class="ui-nav-item {{ $groupActive ? 'font-semibold' : '' }}">
                            <span class="{{ $groupActive ? 'text-maqam-gold-dark' : '' }}">{!! str_replace('h-4 w-4', 'h-[18px] w-[18px]', $group['icon']) !!}</span>
                            <span class="flex-1 text-start">{{ $group['label'] }}</span>
                            @if($groupBadge > 0)
                                <span class="ui-nav-badge">{{ $groupBadge }}</span>
                            @endif
                            <svg class="h-4 w-4 text-maqam-muted transition-transform duration-200" :class="isOpen('{{ $groupKey }}') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul x-show="isOpen('{{ $groupKey }}')" x-collapse class="mt-0.5 space-y-0.5 {{ $isRtl ? 'pr-8' : 'pl-8' }}">
                            @foreach($group['items'] as $link)
                                @php $active = request()->routeIs($link['match']) || (!empty($link['also']) && request()->routeIs(...$link['also'])); @endphp
                                <li>
                                    <a href="{{ route($link['route']) }}" class="ui-nav-sub {{ $active ? 'is-active' : '' }}">
                                        <span class="truncate">{{ $link['label'] }}</span>
                                        @if(!empty($link['badge']))
                                            <span class="ui-nav-badge">{{ $link['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>

        <div class="space-y-1 px-4 pb-5 pt-3">
            <div class="flex items-center gap-3 rounded-2xl border border-[#ECECEF] bg-white px-3 py-2.5 shadow-[0_1px_2px_rgba(18,19,23,0.04)]">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-maqam-tint text-sm font-bold text-maqam-gold-dark">
                    {{ mb_substr(auth()->user()->full_name ?? 'A', 0, 1) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ auth()->user()->full_name }}</span>
                    <span class="block truncate text-[11px] text-maqam-muted">{{ auth()->user()->admin?->role }}</span>
                </span>
            </div>
            @if($can('settings'))
                <a href="{{ route('admin.settings.index') }}" class="ui-nav-item {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ __('admin.nav.settings') }}</span>
                </a>
            @endif
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="ui-nav-item">
                    <svg class="h-[18px] w-[18px] {{ $isRtl ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12H3m0 0l4-4m-4 4l4 4m6-12h5a2 2 0 012 2v12a2 2 0 01-2 2h-5"/></svg>
                    <span>{{ __('admin.logout') }}</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <main class="min-w-0 flex-1 {{ $isRtl ? 'mr-72' : 'ml-72' }}">
        <header class="sticky top-0 z-20 bg-maqam-bg/90 px-6 pb-4 pt-6 backdrop-blur lg:px-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    @hasSection('breadcrumbs')
                        <div class="mb-1 text-xs text-maqam-muted">@yield('breadcrumbs')</div>
                    @endif
                    <h1 class="truncate text-[1.75rem] font-semibold leading-tight tracking-tight text-maqam-ink">@yield('title', __('admin.home.title'))</h1>
                    @hasSection('subtitle')
                        <p class="mt-1 text-sm text-maqam-muted">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form action="{{ route('admin.search') }}" method="GET" class="hidden md:block">
                        <label class="relative block">
                            <svg class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-maqam-muted {{ $isRtl ? 'right-3' : 'left-3' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                            <input type="search" name="q" value="{{ request('q') }}"
                                   placeholder="{{ __('admin.search_global') }}"
                                   class="ui-input w-64 rounded-full {{ $isRtl ? 'pr-9' : 'pl-9' }}">
                        </label>
                    </form>

                    <a href="{{ route('admin.merchants.inbox') }}" class="ui-header-icon relative rounded-full" title="{{ __('admin.nav.merchant_inbox') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($badgeTotal > 0)
                            <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} flex h-5 min-w-5 items-center justify-center rounded-full bg-maqam-gold px-1 text-[10px] font-bold text-maqam-navy">{{ $badgeTotal }}</span>
                        @endif
                    </a>

                    <form method="POST" action="{{ route('admin.locale') }}" class="flex overflow-hidden rounded-full border border-[#E6E6EA] bg-white p-0.5 text-xs font-semibold">
                        @csrf
                        <button name="locale" value="ar" class="rounded-full px-3 py-1.5 {{ $locale === 'ar' ? 'bg-maqam-ink text-white' : 'text-maqam-muted hover:text-maqam-ink' }}">ع</button>
                        <button name="locale" value="en" class="rounded-full px-3 py-1.5 {{ $locale === 'en' ? 'bg-maqam-ink text-white' : 'text-maqam-muted hover:text-maqam-ink' }}">EN</button>
                    </form>

                    @yield('actions')
                </div>
            </div>
        </header>

        <div class="px-6 pb-10 pt-2 lg:px-10">
            @if(session('success'))
                <div class="maqam-toast ui-toast-ok mb-6">
                    {{ session('success') }}
                    @if(session('download_batch'))
                        <a class="{{ $isRtl ? 'mr-2' : 'ml-2' }} font-semibold text-maqam-gold-dark underline" href="{{ route('admin.qr-codes.download', session('download_batch')) }}">{{ __('admin.qr.download_zip') }}</a>
                    @endif
                </div>
            @endif
            @if(session('error'))
                <div class="maqam-toast ui-toast-err mb-6">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="maqam-toast ui-toast-err mb-6">
                    <ul class="list-disc {{ $isRtl ? 'pr-4' : 'pl-4' }}">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<script>
    function sidebarNav() {
        return {
            expanded: (function() {
                try {
                    const saved = localStorage.getItem('maqam_sidebar_expanded_v3');
                    if (saved !== null) {
                        return JSON.parse(saved);
                    }
                } catch(e) {}
                return [];
            })(),
            init() {
                const activeGroup = '{{ $autoExpandGroupKey }}';
                if (activeGroup !== '' && !this.expanded.includes(activeGroup)) {
                    this.expanded.push(activeGroup);
                }
                this.$watch('expanded', val => {
                    try { localStorage.setItem('maqam_sidebar_expanded_v3', JSON.stringify(val)); } catch(e) {}
                });
            },
            isOpen(key) {
                return this.expanded.includes(String(key));
            },
            toggle(key) {
                key = String(key);
                if (this.isOpen(key)) {
                    this.expanded = this.expanded.filter(k => k !== key);
                } else {
                    this.expanded.push(key);
                }
            },
            expandAll() {
                this.expanded = ['0', '1', '2', '3', '4'];
            },
            collapseAll() {
                this.expanded = [];
            },
            toggleAll() {
                if (this.expanded.length > 0) {
                    this.collapseAll();
                } else {
                    this.expandAll();
                }
            }
        };
    }

    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            const t = document.createElement('div');
            t.className = 'maqam-toast fixed bottom-6 z-50 rounded-xl bg-maqam-navy px-4 py-2 text-sm text-white';
            t.style.{{ $isRtl ? 'left' : 'right' }} = '1.5rem';
            t.textContent = @json(__('admin.copied'));
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 1800);
        });
    }
</script>
@stack('scripts')
</body>
</html>
