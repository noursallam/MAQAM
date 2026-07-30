<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.home.title')) — MAQAM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    $badgeTotal = $pendingMerchants + $newOrders;

    $navGroups = [
        [
            'key' => 'store_ops',
            'label' => __('admin.nav.store_ops'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
            'items' => [
                ['route' => 'admin.orders.index', 'label' => __('admin.nav.orders_pipeline'), 'match' => 'admin.orders.*', 'badge' => $newOrders ?: null],
                ['route' => 'admin.categories.index', 'label' => __('admin.nav.store_categories'), 'match' => 'admin.categories.*', 'badge' => null],
                ['route' => 'admin.products.index', 'label' => __('admin.nav.products'), 'match' => 'admin.products.*', 'badge' => null],
                ['route' => 'admin.inventory.index', 'label' => __('admin.nav.inventory'), 'match' => 'admin.inventory.*', 'badge' => null],
            ],
        ],
        [
            'key' => 'people',
            'label' => __('admin.nav.people'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'items' => [
                ['route' => 'admin.customers.index', 'label' => __('admin.nav.customers'), 'match' => 'admin.customers.*', 'badge' => null],
                ['route' => 'admin.merchants.index', 'label' => __('admin.nav.merchants'), 'match' => 'admin.merchants.index', 'badge' => null, 'also' => ['admin.merchants.create', 'admin.merchants.edit', 'admin.merchants.store', 'admin.merchants.update']],
                ['route' => 'admin.merchants.inbox', 'label' => __('admin.nav.merchant_inbox'), 'match' => 'admin.merchants.inbox', 'badge' => $pendingMerchants ?: null],
            ],
        ],
        [
            'key' => 'loyalty',
            'label' => __('admin.nav.loyalty'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 13C10.832 21 4 17.5 4 11V7l8-3 8 3v4c0 6.5-6.832 10-8 10z"/></svg>',
            'items' => [
                ['route' => 'admin.ranks.index', 'label' => __('admin.nav.ranks'), 'match' => 'admin.ranks.*', 'badge' => null],
                ['route' => 'admin.loyalty.transactions', 'label' => __('admin.nav.points_ledger'), 'match' => 'admin.loyalty.transactions', 'badge' => null],
                ['route' => 'admin.loyalty.spins', 'label' => __('admin.nav.lucky_wheel'), 'match' => 'admin.loyalty.spins', 'badge' => null],
                ['route' => 'admin.coupons.index', 'label' => __('admin.nav.coupons'), 'match' => 'admin.coupons.*', 'badge' => null],
                ['route' => 'admin.prize-categories.index', 'label' => __('admin.nav.prize_categories'), 'match' => 'admin.prize-categories.*', 'badge' => null],
                ['route' => 'admin.qr-codes.create', 'label' => __('admin.nav.generate_batch'), 'match' => 'admin.qr-codes.create', 'badge' => null],
                ['route' => 'admin.qr-codes.index', 'label' => __('admin.nav.batches'), 'match' => 'admin.qr-codes.index', 'badge' => null],
                ['route' => 'admin.scans.index', 'label' => __('admin.nav.scan_monitor'), 'match' => 'admin.scans.*', 'badge' => null],
            ],
        ],
        [
            'key' => 'communications',
            'label' => __('admin.nav.communications'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
            'items' => [
                ['route' => 'admin.notifications.create', 'label' => __('admin.nav.notifications_composer'), 'match' => 'admin.notifications.create', 'badge' => null],
                ['route' => 'admin.notifications.index', 'label' => __('admin.nav.notifications_history'), 'match' => 'admin.notifications.index', 'badge' => null],
            ],
        ],
        [
            'key' => 'system',
            'label' => __('admin.nav.system'),
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'items' => [
                ['route' => 'admin.risk.index', 'label' => __('admin.nav.risk_desk'), 'match' => 'admin.risk.*', 'badge' => null],
                ['route' => 'admin.settings.index', 'label' => __('admin.nav.settings'), 'match' => 'admin.settings.*', 'badge' => null],
                ['route' => 'admin.admins.index', 'label' => __('admin.nav.admins_rbac'), 'match' => 'admin.admins.*', 'badge' => null],
            ],
        ],
    ];

    $activeGroupKey = '';
    foreach ($navGroups as $idx => $group) {
        foreach ($group['items'] as $item) {
            if (request()->routeIs($item['match']) || (!empty($item['also']) && request()->routeIs(...$item['also']))) {
                $activeGroupKey = (string)$idx;
                break 2;
            }
        }
    }
    $autoExpandGroupKey = ($activeGroupKey !== '' && !request()->routeIs('admin.dashboard')) ? $activeGroupKey : '';
@endphp
<body class="min-h-screen bg-maqam-bg text-maqam-ink antialiased" style="font-family:'Alexandria',sans-serif">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-30 flex w-72 flex-col bg-maqam-navy text-white shadow-xl">
        <div class="border-b border-white/10 px-6 py-5">
            <div class="text-2xl font-bold tracking-wide text-maqam-gold-light">MAQAM</div>
            <div class="mt-1 text-xs text-white/50">{{ __('admin.admin_panel') }}</div>
            <div class="mt-0.5 text-[11px] text-white/35">{{ __('admin.store_console') }}</div>
        </div>

        <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 py-4 text-sm" x-data="sidebarNav()">
            <div class="mb-3 flex items-center justify-between border-b border-white/10 px-2 pb-2.5">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">{{ $isRtl ? 'أقسام النظام' : 'SYSTEM SECTIONS' }}</span>
                <button type="button" @click="toggleAll()" 
                        class="flex items-center gap-1 rounded px-2 py-1 text-[11px] font-medium text-white/60 hover:bg-white/10 hover:text-white transition-colors">
                    <svg class="h-3 w-3 transition-transform duration-200" :class="expanded.length > 0 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <span x-text="expanded.length > 0 ? '{{ $isRtl ? 'طي الكل' : 'Collapse All' }}' : '{{ $isRtl ? 'توسيع الكل' : 'Expand All' }}'"></span>
                </button>
            </div>

            @php $dashboardActive = request()->routeIs('admin.dashboard'); @endphp
            <a href="{{ route('admin.dashboard') }}"
               class="mb-2.5 flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-xs font-semibold transition-all {{ $dashboardActive ? 'border-maqam-gold bg-maqam-gold text-maqam-navy shadow-sm' : 'border-white/[0.06] bg-white/[0.03] text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-4 w-4 {{ $dashboardActive ? 'text-maqam-navy' : 'text-maqam-gold-light' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                </svg>
                <span>{{ __('admin.nav.command_center') }}</span>
            </a>

            @foreach($navGroups as $groupKey => $group)
                <div class="mb-2.5 rounded-xl bg-white/[0.03] p-1.5 border border-white/[0.06] transition-all">
                    <button type="button" 
                            @click="toggle('{{ $groupKey }}')" 
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-xs font-semibold tracking-wide text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="text-maqam-gold-light">{!! $group['icon'] !!}</span>
                            <span>{{ $group['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="rounded-full bg-white/10 px-1.5 py-0.2 text-[10px] font-mono text-white/50">{{ count($group['items']) }}</span>
                            <svg class="h-3.5 w-3.5 transition-transform duration-200" 
                                 :class="isOpen('{{ $groupKey }}') ? 'rotate-180 text-maqam-gold-light' : 'rotate-0 text-white/40'" 
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-show="isOpen('{{ $groupKey }}')" x-collapse class="space-y-0.5 mt-1 px-0.5 pb-0.5">
                        @foreach($group['items'] as $link)
                            @php $active = request()->routeIs($link['match']) || (!empty($link['also']) && request()->routeIs(...$link['also'])); @endphp
                            <li>
                                <a href="{{ route($link['route']) }}" 
                                   class="flex items-center justify-between rounded-lg px-3 py-2 text-xs transition-all {{ $active ? 'bg-maqam-gold text-maqam-navy font-bold shadow-sm' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                                    <span>{{ $link['label'] }}</span>
                                    @if(!empty($link['badge']))
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $active ? 'bg-maqam-navy text-white' : 'bg-maqam-gold text-maqam-navy' }}">{{ $link['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="mb-3 rounded-lg border border-white/10 bg-white/5 p-3 text-xs text-white/60">
                <div class="font-medium text-white">{{ auth()->user()->full_name }}</div>
                <div class="mt-0.5">{{ auth()->user()->admin?->role }}</div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="w-full rounded-lg border border-white/15 px-3 py-2 text-sm text-white/70 hover:bg-white/5">{{ __('admin.logout') }}</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <main class="flex-1 {{ $isRtl ? 'mr-72' : 'ml-72' }}">
        <header class="sticky top-0 z-20 border-b border-[#D8D4CB] bg-[#F3F1EB]/95 px-6 py-4 backdrop-blur lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    @hasSection('breadcrumbs')
                        <div class="mb-1 text-xs text-maqam-muted">@yield('breadcrumbs')</div>
                    @endif
                    <h1 class="truncate text-xl font-semibold text-maqam-ink">@yield('title', __('admin.home.title'))</h1>
                    @hasSection('subtitle')
                        <p class="mt-0.5 text-sm text-maqam-muted">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form action="{{ route('admin.search') }}" method="GET" class="hidden md:block">
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('admin.search_global') }}"
                               class="ui-input w-64">
                    </form>

                    <a href="{{ route('admin.merchants.inbox') }}" class="ui-header-icon relative" title="{{ __('admin.nav.merchant_inbox') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($badgeTotal > 0)
                            <span class="absolute -top-1 {{ $isRtl ? '-left-1' : '-right-1' }} flex h-5 min-w-5 items-center justify-center rounded-full bg-maqam-gold px-1 text-[10px] font-bold text-maqam-navy">{{ $badgeTotal }}</span>
                        @endif
                    </a>

                    <form method="POST" action="{{ route('admin.locale') }}" class="flex overflow-hidden rounded-lg border border-[#D8D4CB] bg-white text-xs font-semibold">
                        @csrf
                        <button name="locale" value="ar" class="px-3 py-2 {{ $locale === 'ar' ? 'bg-maqam-gold text-maqam-navy' : 'text-maqam-muted hover:text-maqam-ink' }}">ع</button>
                        <button name="locale" value="en" class="px-3 py-2 {{ $locale === 'en' ? 'bg-maqam-gold text-maqam-navy' : 'text-maqam-muted hover:text-maqam-ink' }}">EN</button>
                    </form>

                    @yield('actions')
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
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
