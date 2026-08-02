@extends('store.layouts.app')

@section('title', __('store.product.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.shop') }}">{{ __('store.nav.shop') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.products.wall_socket') }}</span>
        </div>

        <div class="mq-product-layout">
            <div class="mq-product-gallery">
                <svg width="140" height="140" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.1" aria-hidden="true">
                    <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
                </svg>
            </div>

            <div class="mq-product-info">
                <span class="mq-eyebrow">{{ __('store.categories.sockets') }}</span>
                <h1>{{ __('store.products.wall_socket') }}</h1>
                <div class="mq-product-meta">
                    <span>{{ __('store.common.rating') }}: 4.8 / 5</span>
                    <span>{{ __('store.common.available') }}: 48 {{ __('store.product.pieces') }}</span>
                    <span>{{ __('store.product.sku') }}: #{{ $productId ?? 1 }}</span>
                </div>
                <div class="mq-product-price">45 {{ __('store.common.egp') }}</div>
                <p style="color:var(--mq-muted);margin:0 0 1rem">{{ __('store.product.desc_text') }}</p>

                <div class="mq-loyalty-inline">
                    <strong>{{ __('store.product.loyalty_badge') }}</strong>
                    <span>{{ __('store.product.loyalty_hint') }}</span>
                </div>

                <div class="mq-qty" aria-label="{{ __('store.common.qty') }}">
                    <button type="button">−</button>
                    <span>1</span>
                    <button type="button">+</button>
                </div>

                <div class="mq-product-actions">
                    <a href="{{ route('store.cart') }}" class="mq-btn mq-btn-primary">{{ __('store.product.add_cart') }}</a>
                    <a href="{{ route('store.checkout') }}" class="mq-btn mq-btn-ghost">{{ __('store.product.buy_now') }}</a>
                </div>

                <div class="mq-tabs" role="tablist">
                    <button type="button" class="is-active">{{ __('store.product.description') }}</button>
                    <button type="button">{{ __('store.product.specs') }}</button>
                    <button type="button">{{ __('store.product.loyalty_tab') }}</button>
                </div>
                <div class="mq-panel">
                    <p style="margin:0;color:var(--mq-muted)">{{ __('store.product.panel_text') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
