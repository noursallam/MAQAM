@extends('store.layouts.app')

@section('title', __('store.auth.login_title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">{{ __('store.auth.login_heading') }}</h1>
            <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.login_lead') }}</p>
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>{{ __('store.auth.phone') }}</label>
                    <input type="tel" placeholder="01xxxxxxxxx" dir="ltr">
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">{{ __('store.auth.send_otp') }}</button>
                <div class="mq-auth-alt">
                    {{ __('store.auth.no_account') }} <a href="{{ route('store.register') }}">{{ __('store.auth.register') }}</a>
                    · <a href="{{ route('store.profile') }}">{{ __('store.auth.preview_account') }}</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
