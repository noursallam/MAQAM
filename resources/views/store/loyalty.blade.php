@extends('store.layouts.app')

@section('title', __('store.loyalty.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.nav.loyalty') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.loyalty.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.loyalty.lead') }}</p>

        <div class="mq-loyalty-steps">
            <div class="mq-panel">
                <span class="mq-step-num">1</span>
                <h3>{{ __('store.loyalty.step1_title') }}</h3>
                <p>{{ __('store.loyalty.step1_text') }}</p>
            </div>
            <div class="mq-panel">
                <span class="mq-step-num">2</span>
                <h3>{{ __('store.loyalty.step2_title') }}</h3>
                <p>{{ __('store.loyalty.step2_text') }}</p>
            </div>
            <div class="mq-panel">
                <span class="mq-step-num">3</span>
                <h3>{{ __('store.loyalty.step3_title') }}</h3>
                <p>{{ __('store.loyalty.step3_text') }}</p>
            </div>
        </div>

        <div class="mq-about-grid" style="margin-top:1.25rem">
            <div class="mq-panel mq-prose">
                <h3>{{ __('store.loyalty.ranks') }}</h3>
                <ul>
                    <li><strong>{{ __('store.loyalty.silver') }}</strong> {{ __('store.loyalty.silver_text') }}</li>
                    <li><strong>{{ __('store.loyalty.gold') }}</strong> {{ __('store.loyalty.gold_text') }}</li>
                    <li><strong>{{ __('store.loyalty.platinum') }}</strong> {{ __('store.loyalty.platinum_text') }}</li>
                </ul>
                <h3>{{ __('store.loyalty.wheel') }}</h3>
                <p>{{ __('store.loyalty.wheel_text') }}</p>
            </div>
            <div class="mq-panel mq-prose">
                <h3>{{ __('store.loyalty.notes') }}</h3>
                <ul>
                    <li>{{ __('store.loyalty.note_1') }}</li>
                    <li>{{ __('store.loyalty.note_2') }}</li>
                    <li>{{ __('store.loyalty.note_3') }}</li>
                    <li>{{ __('store.loyalty.note_4') }}</li>
                </ul>
                <a href="{{ route('store.profile') }}" class="mq-btn mq-btn-primary">{{ __('store.loyalty.my_wallet') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
