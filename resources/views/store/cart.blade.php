@extends('store.layouts.app')

@section('title', __('store.cart.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.cart.breadcrumb') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.cart.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.cart.lead') }}</p>

        <div class="mq-cart-layout">
            <div class="mq-panel" style="overflow:auto">
                <table class="mq-table">
                    <thead>
                        <tr>
                            <th>{{ __('store.cart.product') }}</th>
                            <th>{{ __('store.common.price') }}</th>
                            <th>{{ __('store.common.qty') }}</th>
                            <th>{{ __('store.common.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="mq-cart-item">
                                    <div class="mq-cart-thumb">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.4"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                                    </div>
                                    <div>
                                        <strong>{{ __('store.products.wall_socket') }}</strong>
                                        <div style="color:var(--mq-muted);font-size:.85rem">{{ __('store.categories.sockets') }} · +50 {{ __('store.common.points') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>45 {{ __('store.common.egp') }}</td>
                            <td>
                                <div class="mq-qty">
                                    <button type="button">−</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>
                            </td>
                            <td>45 {{ __('store.common.egp') }}</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="mq-cart-item">
                                    <div class="mq-cart-thumb">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.4"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                                    </div>
                                    <div>
                                        <strong>{{ __('store.products.multi_plug') }}</strong>
                                        <div style="color:var(--mq-muted);font-size:.85rem">{{ __('store.categories.sockets') }} · +80 {{ __('store.common.points') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>85 {{ __('store.common.egp') }}</td>
                            <td>
                                <div class="mq-qty">
                                    <button type="button">−</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>
                            </td>
                            <td>85 {{ __('store.common.egp') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <aside class="mq-panel">
                <h3 class="mq-side-title">{{ __('store.cart.summary') }}</h3>
                <div class="mq-summary-row"><span>{{ __('store.common.subtotal') }}</span><span>130 {{ __('store.common.egp') }}</span></div>
                <div class="mq-summary-row"><span>{{ __('store.common.shipping') }}</span><span>35 {{ __('store.common.egp') }}</span></div>
                <div class="mq-summary-row total"><span>{{ __('store.common.total') }}</span><span>165 {{ __('store.common.egp') }}</span></div>
                <a href="{{ route('store.checkout') }}" class="mq-btn mq-btn-primary mq-btn-block" style="margin-top:1rem">{{ __('store.cart.checkout') }}</a>
                <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost mq-btn-block" style="margin-top:.6rem">{{ __('store.common.continue_shopping') }}</a>
            </aside>
        </div>
    </div>
</section>
@endsection
