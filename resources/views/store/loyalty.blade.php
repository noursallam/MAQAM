@extends('store.layouts.app')

@section('title', __('store.loyalty.title'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

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

        <div class="mq-about-grid" style="margin-top:1.5rem">
            <div class="mq-panel mq-prose">
                <h3>{{ __('store.loyalty.ranks') }} (رتب الأعضاء والولاء)</h3>
                <ul>
                    @foreach ($ranks as $r)
                        <li>
                            <strong style="color:var(--mq-gold);">{{ $locale === 'ar' ? $r->name_ar : $r->name_en }}</strong>:
                            تبدأ من {{ number_format($r->min_points) }} نقطة 
                            @if ($r->max_points)
                                حتى {{ number_format($r->max_points) }} نقطة.
                            @else
                                فما فوق.
                            @endif
                            (مكافأة مسح QR: {{ $r->customer_points_per_scan }} نقطة لكل مسح).
                        </li>
                    @endforeach
                </ul>

                @if ($wheelEnabled && $wheelPrizes->isNotEmpty())
                    <h3 style="margin-top:2rem;">{{ __('store.loyalty.wheel') }}</h3>
                    <p>{{ __('store.loyalty.wheel_text') }}</p>
                    <p style="color:var(--mq-muted);font-size:.92rem;">
                        جوائز العجلة المتاحة: 
                        {{ $wheelPrizes->pluck($locale === 'ar' ? 'name_ar' : 'name_en')->filter()->join('، ') }}.
                    </p>
                @endif
            </div>

            <div class="mq-panel mq-prose">
                <h3>{{ __('store.loyalty.notes') }}</h3>
                <ul>
                    <li>{{ __('store.loyalty.note_1') }}</li>
                    <li>{{ __('store.loyalty.note_2') }}</li>
                    <li>{{ __('store.loyalty.note_3') }}</li>
                    <li>يمكنك استبدال نقاطك في أي وقت كخصم مباشر أثناء إتمام الشراء عند اختيار الدفع عبر المحفظة.</li>
                </ul>
                <div style="margin-top:1.5rem;">
                    @auth
                        <a href="{{ route('store.profile') }}#wallet" class="mq-btn mq-btn-primary">{{ __('store.loyalty.my_wallet') }}</a>
                    @else
                        <a href="{{ route('store.register') }}" class="mq-btn mq-btn-primary">أنشئ حسابك وابدأ جمع النقاط</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
