@extends('store.layouts.app')

@section('title', __('store.auth.register_title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">{{ __('store.auth.register_heading') }}</h1>
            <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.register_lead') }}</p>
            <form class="mq-panel" action="{{ route('store.register.submit') }}" method="POST">
                @csrf
                <div class="mq-field">
                    <label for="reg_name">{{ __('store.auth.full_name') }} *</label>
                    <input type="text" id="reg_name" name="full_name" value="{{ old('full_name') }}" placeholder="{{ __('store.auth.name_ph') }}" required autofocus>
                </div>
                <div class="mq-field">
                    <label for="reg_phone">{{ __('store.auth.phone') }} *</label>
                    <input type="tel" id="reg_phone" name="phone" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" required>
                </div>
                <div class="mq-field">
                    <label for="reg_email">{{ __('store.auth.email') }}</label>
                    <input type="email" id="reg_email" name="email" value="{{ old('email') }}" placeholder="name@email.com" dir="ltr">
                </div>
                <div class="mq-field">
                    <label for="reg_password">{{ __('store.auth.password') }} *</label>
                    <input type="password" id="reg_password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">{{ __('store.auth.create') }}</button>
                <div class="mq-auth-alt">
                    {{ __('store.auth.have_account') }} <a href="{{ route('store.login') }}">{{ __('store.auth.login_heading') }}</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
