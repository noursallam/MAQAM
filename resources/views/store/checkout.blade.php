@extends('store.layouts.app')

@section('title', __('store.checkout.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.cart') }}">{{ __('store.nav.cart') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.checkout.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.checkout.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.checkout.lead') }}</p>

        <div class="mq-checkout-layout">
            <form class="mq-panel" onsubmit="return false;">
                <h3 class="mq-side-title">{{ __('store.checkout.shipping_data') }}</h3>
                <div class="mq-field">
                    <label>{{ __('store.checkout.full_name') }}</label>
                    <input type="text" placeholder="{{ __('store.checkout.full_name_ph') }}">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.checkout.phone') }}</label>
                    <input type="tel" placeholder="01xxxxxxxxx" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.checkout.city') }}</label>
                    <select>
                        <option>{{ __('store.checkout.cairo') }}</option>
                        <option>{{ __('store.checkout.giza') }}</option>
                        <option>{{ __('store.checkout.alex') }}</option>
                        <option>{{ __('store.checkout.mansoura') }}</option>
                    </select>
                </div>
                <div class="mq-field">
                    <label>{{ __('store.checkout.address') }}</label>
                    <textarea placeholder="{{ __('store.checkout.address_ph') }}"></textarea>
                </div>
                <h3 class="mq-side-title">{{ __('store.checkout.payment') }}</h3>
                <div class="mq-filter-group">
                    <label><input type="radio" name="pay" checked> {{ __('store.checkout.cod') }}</label>
                    <label><input type="radio" name="pay"> {{ __('store.checkout.paymob') }}</label>
                    <label><input type="radio" name="pay"> {{ __('store.checkout.wallet') }}</label>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary" style="margin-top:1rem">{{ __('store.checkout.confirm') }}</button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">{{ __('store.checkout.summary') }}</h3>
                <div class="mq-summary-row"><span>{{ __('store.checkout.two_items') }}</span><span>130 {{ __('store.common.egp') }}</span></div>
                <div class="mq-summary-row"><span>{{ __('store.common.shipping') }}</span><span>35 {{ __('store.common.egp') }}</span></div>
                <div class="mq-summary-row total"><span>{{ __('store.common.total') }}</span><span>165 {{ __('store.common.egp') }}</span></div>
            </aside>
        </div>
    </div>
</section>
@endsection
