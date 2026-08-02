@extends('store.layouts.app')

@section('title', __('store.profile.title'))

@section('content')
@php
    $avatar = app()->getLocale() === 'ar' ? 'أ م' : 'AM';
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
                    <h2>{{ __('store.profile.demo_name') }}</h2>
                    <p dir="ltr">01012345678</p>
                    <span class="mq-rank-badge">{{ __('store.profile.rank_gold') }}</span>
                    <div class="mq-profile-nav">
                        <a href="#wallet" class="is-active">{{ __('store.profile.wallet_nav') }}</a>
                        <a href="#orders">{{ __('store.profile.orders_nav') }}</a>
                        <a href="#tx">{{ __('store.profile.tx_nav') }}</a>
                        <a href="#settings">{{ __('store.profile.settings_nav') }}</a>
                        <a href="{{ route('store.loyalty') }}">{{ __('store.profile.loyalty_nav') }}</a>
                    </div>
                </div>
            </aside>

            <div class="mq-profile-main">
                <div class="mq-panel" id="wallet">
                    <h3 class="mq-side-title">{{ __('store.profile.wallet') }}</h3>
                    <div class="mq-wallet-grid">
                        <div class="mq-wallet-stat">
                            <span>{{ __('store.profile.balance') }}</span>
                            <strong>2,450</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>{{ __('store.profile.current_rank') }}</span>
                            <strong>{{ __('store.profile.gold') }}</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>{{ __('store.profile.to_platinum') }}</span>
                            <strong>{{ __('store.profile.points_left') }}</strong>
                        </div>
                    </div>
                    <div class="mq-progress">
                        <div class="mq-progress-bar" style="width:78%"></div>
                    </div>
                    <p class="mq-muted-note">{{ __('store.profile.wallet_hint') }}</p>
                    <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">{{ __('store.profile.how_loyalty') }}</a>
                </div>

                <div class="mq-panel" id="orders">
                    <h3 class="mq-side-title">{{ __('store.profile.recent_orders') }}</h3>
                    <div class="mq-order-list">
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1042</strong>
                                <span>{{ __('store.products.wall_socket') }} + {{ __('store.products.multi_plug') }}</span>
                            </div>
                            <em class="mq-status is-shipped">{{ __('store.profile.shipped') }}</em>
                            <span>325 {{ __('store.common.egp') }}</span>
                        </div>
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1031</strong>
                                <span>{{ __('store.products.breaker') }}</span>
                            </div>
                            <em class="mq-status is-done">{{ __('store.profile.delivered') }}</em>
                            <span>95 {{ __('store.common.egp') }}</span>
                        </div>
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1018</strong>
                                <span>{{ __('store.products.cable') }}</span>
                            </div>
                            <em class="mq-status is-prep">{{ __('store.profile.preparing') }}</em>
                            <span>220 {{ __('store.common.egp') }}</span>
                        </div>
                    </div>
                </div>

                <div class="mq-panel" id="tx">
                    <h3 class="mq-side-title">{{ __('store.profile.transactions') }}</h3>
                    <div class="mq-tx-list">
                        <div class="mq-tx-row"><span>{{ __('store.profile.tx_scan_socket') }}</span><strong class="up">+50</strong></div>
                        <div class="mq-tx-row"><span>{{ __('store.profile.tx_scan_cable') }}</span><strong class="up">+120</strong></div>
                        <div class="mq-tx-row"><span>{{ __('store.profile.tx_wheel') }}</span><strong class="down">−30</strong></div>
                        <div class="mq-tx-row"><span>{{ __('store.profile.tx_coupon') }}</span><strong class="up">{{ __('store.profile.coupon') }}</strong></div>
                    </div>
                </div>

                <div class="mq-panel" id="settings">
                    <h3 class="mq-side-title">{{ __('store.profile.account_data') }}</h3>
                    <form class="mq-profile-form" onsubmit="return false;">
                        <div class="mq-field">
                            <label>{{ __('store.profile.full_name') }}</label>
                            <input type="text" value="{{ __('store.profile.demo_name') }}">
                        </div>
                        <div class="mq-field">
                            <label>{{ __('store.profile.phone') }}</label>
                            <input type="tel" value="01012345678" dir="ltr">
                        </div>
                        <div class="mq-field">
                            <label>{{ __('store.profile.city') }}</label>
                            <input type="text" value="{{ __('store.profile.demo_city') }}">
                        </div>
                        <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.common.save') }}</button>
                    </form>
                    <div class="mq-merchant-cta">
                        <strong>{{ __('store.profile.merchant_title') }}</strong>
                        <p>{{ __('store.profile.merchant_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
