@extends('store.layouts.app')

@section('title', __('store.auth.login_title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">{{ __('store.auth.login_heading') }}</h1>
            <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.login_lead') }}</p>
            <form class="mq-panel" action="{{ route('store.login.submit') }}" method="POST">
                @csrf
                <div class="mq-field">
                    <label for="login_phone">{{ __('store.auth.phone') }}</label>
                    <input type="tel" id="login_phone" name="phone" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" required autofocus>
                </div>
                <div class="mq-field">
                    <label for="login_password">{{ __('store.auth.password') ?? 'كلمة المرور' }}</label>
                    <input type="password" id="login_password" name="password" placeholder="••••••••">
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">{{ __('store.auth.login_heading') }}</button>
                <div class="mq-auth-alt">
                    {{ __('store.auth.no_account') }} <a href="{{ route('store.register') }}">{{ __('store.auth.register') }}</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
