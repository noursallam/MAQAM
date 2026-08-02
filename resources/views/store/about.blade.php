@extends('store.layouts.app')

@section('title', __('store.about.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.about.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.about.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.about.lead') }}</p>

        <div class="mq-about-grid">
            <div class="mq-panel mq-prose">
                <h3>{{ __('store.about.purpose') }}</h3>
                <p>{{ __('store.about.purpose_text') }}</p>
                <div class="mq-stat-grid">
                    <div class="mq-stat"><strong>QR</strong><span>{{ __('store.about.stat_qr') }}</span></div>
                    <div class="mq-stat"><strong>{{ __('store.about.ranks_label') }}</strong><span>{{ __('store.about.stat_ranks') }}</span></div>
                    <div class="mq-stat"><strong>{{ __('store.about.wheel_label') }}</strong><span>{{ __('store.about.stat_wheel') }}</span></div>
                </div>
            </div>
            <div class="mq-panel mq-prose">
                <h3>{{ __('store.about.what') }}</h3>
                <ul>
                    <li>{{ __('store.about.what_1') }}</li>
                    <li>{{ __('store.about.what_2') }}</li>
                    <li>{{ __('store.about.what_3') }}</li>
                    <li>{{ __('store.about.what_4') }}</li>
                </ul>
                <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-primary">{{ __('store.about.learn_loyalty') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
