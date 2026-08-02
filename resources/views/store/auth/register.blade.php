@extends('store.layouts.app')

@section('title', __('store.auth.register_title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">{{ __('store.auth.register_heading') }}</h1>
            <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.register_lead') }}</p>
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>{{ __('store.auth.full_name') }}</label>
                    <input type="text" placeholder="{{ __('store.auth.name_ph') }}">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.auth.email') }}</label>
                    <input type="email" placeholder="name@email.com" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.auth.password') }}</label>
                    <input type="password" placeholder="••••••••">
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
