@extends('store.layouts.app')

@section('title', __('store.profile.title'))

@section('content')
@php
    $locale = app()->getLocale();
    $avatar = mb_substr($user->full_name, 0, 2);
    $statusColors = [
        'new' => 'background:rgba(59,130,246,.15);color:#60a5fa;',
        'processing' => 'background:rgba(234,179,8,.15);color:#facc15;',
        'shipped' => 'background:rgba(168,85,247,.15);color:#c084fc;',
        'delivered' => 'background:rgba(34,197,94,.15);color:#22c55e;',
        'cancelled' => 'background:rgba(239,68,68,.15);color:#f87171;',
    ];
    $statusLabels = [
        'new' => 'طلب جديد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];
@endphp

<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.profile.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.profile.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.profile.lead') }}</p>

        <div class="mq-profile-layout">
            <aside class="mq-profile-side">
                <div class="mq-panel mq-profile-card">
                    <div class="mq-profile-avatar">{{ $avatar }}</div>
                    <h2>{{ $user->full_name }}</h2>
                    <p dir="ltr">{{ $user->phone_number }}</p>
                    @if ($rank)
                        <span class="mq-rank-badge">{{ $locale === 'ar' ? $rank->name_ar : $rank->name_en }}</span>
                    @endif
                    <div class="mq-profile-nav">
                        <a href="#wallet" class="is-active">{{ __('store.profile.wallet_nav') }}</a>
                        <a href="#orders">{{ __('store.profile.orders_nav') }}</a>
                        <a href="#tx">{{ __('store.profile.tx_nav') }}</a>
                        <a href="#settings">{{ __('store.profile.settings_nav') }}</a>
                        <a href="{{ route('store.loyalty') }}">{{ __('store.profile.loyalty_nav') }}</a>
                        <form action="{{ route('store.logout') }}" method="POST" style="margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--mq-border);">
                            @csrf
                            <button type="submit" class="mq-btn mq-btn-ghost mq-btn-block" style="font-size:.85rem;color:#f87171;">
                                {{ __('store.auth.logout') ?? 'تسجيل الخروج' }}
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="mq-profile-main">
                <!-- Loyalty Wallet -->
                <div class="mq-panel" id="wallet">
                    <h3 class="mq-side-title">{{ __('store.profile.wallet') }}</h3>
                    <div class="mq-wallet-grid">
                        <div class="mq-wallet-stat">
                            <span>{{ __('store.profile.balance') }}</span>
                            <strong>{{ number_format($customer?->points_balance ?? 0) }}</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>{{ __('store.profile.current_rank') }}</span>
                            <strong>{{ $rank ? ($locale === 'ar' ? $rank->name_ar : $rank->name_en) : 'Silver' }}</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>{{ $nextRank ? ($locale === 'ar' ? 'للوصول لـ '.$nextRank->name_ar : 'To '.$nextRank->name_en) : 'الرتبة الأعلى' }}</span>
                            <strong>{{ $nextRank ? number_format($pointsLeft).' '.__('store.common.points') : 'أعلى رتبة ✓' }}</strong>
                        </div>
                    </div>
                    <div class="mq-progress">
                        <div class="mq-progress-bar" style="width:{{ $progressPercent }}%"></div>
                    </div>
                    <p class="mq-muted-note">{{ __('store.profile.wallet_hint') }}</p>
                    <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">{{ __('store.profile.how_loyalty') }}</a>
                </div>

                <!-- Recent Orders -->
                <div class="mq-panel" id="orders">
                    <h3 class="mq-side-title">{{ __('store.profile.recent_orders') }}</h3>
                    @if ($orders->isEmpty())
                        <div style="text-align:center;padding:2rem 1rem;color:var(--mq-muted);">
                            <p style="margin:0 0 1rem;">لم تقم بإنشاء أي طلبات حتى الآن.</p>
                            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.common.shop_now') }}</a>
                        </div>
                    @else
                        <div class="mq-order-list">
                            @foreach ($orders as $o)
                                <div class="mq-order-row" style="padding:.9rem 0;border-bottom:1px solid var(--mq-border);">
                                    <div>
                                        <a href="{{ route('store.order.confirmation', $o->order_number) }}" style="color:inherit;text-decoration:none;">
                                            <strong style="color:var(--mq-gold);">#{{ $o->order_number }}</strong>
                                        </a>
                                        <div style="font-size:.82rem;color:var(--mq-muted);margin-top:.2rem;">
                                            {{ $o->items->pluck('product.name_ar')->filter()->take(2)->join(' + ') ?: 'أدوات كهربائية' }}
                                            · {{ $o->created_at->format('Y/m/d') }}
                                        </div>
                                    </div>
                                    <em class="mq-status" style="border-radius:12px;padding:.2rem .75rem;font-size:.8rem;font-style:normal;{{ $statusColors[$o->status] ?? '' }}">
                                        {{ $statusLabels[$o->status] ?? $o->status }}
                                    </em>
                                    <span style="font-weight:700;">{{ number_format((float) $o->total_amount, 2) }} {{ __('store.common.egp') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Points Transactions -->
                <div class="mq-panel" id="tx">
                    <h3 class="mq-side-title">{{ __('store.profile.transactions') }}</h3>
                    @if ($transactions->isEmpty())
                        <p style="color:var(--mq-muted);margin:0;">لا توجد حركات نقاط مسجلة حالياً.</p>
                    @else
                        <div class="mq-tx-list">
                            @foreach ($transactions as $tx)
                                <div class="mq-tx-row">
                                    <div>
                                        <span>{{ $tx->description ?: 'حركة نقاط' }}</span>
                                        <div style="font-size:.78rem;color:var(--mq-muted);">{{ $tx->created_at->format('Y/m/d H:i') }}</div>
                                    </div>
                                    @if ($tx->amount > 0)
                                        <strong class="up">+{{ $tx->amount }}</strong>
                                    @else
                                        <strong class="down">{{ $tx->amount }}</strong>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Account Settings -->
                <div class="mq-panel" id="settings">
                    <h3 class="mq-side-title">{{ __('store.profile.account_data') }}</h3>
                    <form class="mq-profile-form" action="{{ route('store.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mq-field">
                            <label>{{ __('store.profile.full_name') }}</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                        </div>
                        <div class="mq-field">
                            <label>{{ __('store.profile.phone') }}</label>
                            <input type="tel" value="{{ $user->phone_number }}" dir="ltr" disabled style="opacity:.7;cursor:not-allowed;">
                        </div>
                        <div class="mq-field">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr">
                        </div>
                        <div class="mq-field">
                            <label>{{ __('store.profile.city') }}</label>
                            <input type="text" name="city" value="{{ old('city', $addresses->first()?->city) }}">
                        </div>
                        <div class="mq-field">
                            <label>عنوان التوصيل المفضل</label>
                            <input type="text" name="address" value="{{ old('address', $addresses->first()?->address_line1) }}" placeholder="الشارع، رقم العمارة، الشقة">
                        </div>
                        <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.common.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
