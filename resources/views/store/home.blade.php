@extends('store.layouts.app')

@section('title', __('store.home.title'))
@section('meta_description', __('store.meta_description'))

@section('content')
@php
    $categories = [
        [
            'key' => 'all',
            'count' => 48,
            'tone' => '#1b2434',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        ],
        [
            'key' => 'sockets',
            'count' => 14,
            'tone' => '#243044',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="7" y="3" width="10" height="14" rx="2"/><path d="M10 7v4M14 7v4M9 21h6"/></svg>',
        ],
        [
            'key' => 'switches',
            'count' => 10,
            'tone' => '#1f2937',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="6" width="16" height="12" rx="2"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/></svg>',
        ],
        [
            'key' => 'cables',
            'count' => 8,
            'tone' => '#222b3b',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8c4 0 4 8 8 8s4-8 8-8"/><path d="M4 16c4 0 4-8 8-8s4 8 8 8"/></svg>',
        ],
        [
            'key' => 'lighting',
            'count' => 7,
            'tone' => '#1a2332',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18h6M10 21h4"/><path d="M8 10a4 4 0 1 1 8 0c0 2-1.5 3-2 4H10c-.5-1-2-2-2-4z"/></svg>',
        ],
        [
            'key' => 'breakers',
            'count' => 5,
            'tone' => '#182131',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>',
        ],
    ];
@endphp

<section class="mq-hero" aria-label="{{ __('store.home.title') }}">
    <div class="mq-hero-media">
        <img src="{{ asset('store/img/hero-1.webp') }}" alt="{{ __('store.store_name') }}">
    </div>
    <div class="mq-hero-glow" aria-hidden="true"></div>

    <div class="mq-container mq-hero-content">
        <h1>{{ __('store.home.hero_title') }}</h1>
        <p>{{ __('store.home.hero_text') }}</p>
        <div class="mq-hero-actions">
            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.home.shop_now') }}</a>
            <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">{{ __('store.home.loyalty_cta') }}</a>
        </div>
    </div>
</section>

<section class="mq-features">
    <div class="mq-container mq-features-grid">
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
            </div>
            <div>
                <strong>{{ __('store.home.feature_shipping') }}</strong>
                <span>{{ __('store.home.feature_shipping_text') }}</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-3z"/></svg>
            </div>
            <div>
                <strong>{{ __('store.home.feature_quality') }}</strong>
                <span>{{ __('store.home.feature_quality_text') }}</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
            </div>
            <div>
                <strong>{{ __('store.home.feature_points') }}</strong>
                <span>{{ __('store.home.feature_points_text') }}</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14v3a3 3 0 0 0 3 3h1"/><path d="M20 14v3a3 3 0 0 1-3 3h-1"/><path d="M8 21h8"/><path d="M7 4h10a2 2 0 0 1 2 2v5H5V6a2 2 0 0 1 2-2z"/></svg>
            </div>
            <div>
                <strong>{{ __('store.home.feature_support') }}</strong>
                <span>{{ __('store.home.feature_support_text') }}</span>
            </div>
        </div>
    </div>
</section>

<section class="mq-shop-cats mq-home-cats" aria-label="{{ __('store.home.product_types') }}">
    <div class="mq-container">
        <div class="mq-section-head" style="margin-bottom:1.35rem">
            <span class="mq-eyebrow">{{ __('store.home.browse_by_type') }}</span>
            <h2>{{ __('store.home.product_types') }}</h2>
        </div>
        <div class="mq-cat-strip" role="list">
            @foreach ($categories as $i => $cat)
                <a href="{{ route('store.shop') }}" class="mq-cat-item {{ $i === 0 ? 'is-active' : '' }}" role="listitem">
                    <span class="mq-cat-avatar" style="--cat-tone: {{ $cat['tone'] }}">
                        {!! $cat['icon'] !!}
                    </span>
                    <span class="mq-cat-label">{{ __('store.categories.'.$cat['key']) }}</span>
                    <em class="mq-cat-count">{{ $cat['count'] }} {{ __('store.common.product') }}</em>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="mq-section">
    <div class="mq-container">
        <div class="mq-section-head">
            <span class="mq-eyebrow">{{ __('store.home.best_sellers') }}</span>
            <h2>{{ __('store.home.selected_products') }}</h2>
            <p>{{ __('store.home.selected_lead') }}</p>
        </div>

        <div class="mq-products">
            @include('store.partials.products', [
                'products' => [
                    ['id' => 1, 'name_key' => 'wall_socket', 'cat_key' => 'sockets', 'price' => '45', 'tag' => null, 'tone' => '#1f2937', 'points' => 50],
                    ['id' => 2, 'name_key' => 'multi_plug', 'cat_key' => 'sockets', 'price' => '85', 'old' => '110', 'tag' => '-23%', 'tone' => '#243044', 'points' => 80],
                    ['id' => 3, 'name_key' => 'light_switch', 'cat_key' => 'switches', 'price' => '35', 'tag' => null, 'tone' => '#1a2332', 'points' => 40],
                    ['id' => 4, 'name_key' => 'cable', 'cat_key' => 'cables', 'price' => '220', 'tag' => 'new', 'tone' => '#222b3b', 'points' => 120],
                ],
            ])
        </div>
    </div>
</section>

<section class="mq-section mq-home-faq">
    <div class="mq-container mq-home-faq-inner">
        <div class="mq-section-head">
            <span class="mq-eyebrow">{{ __('store.home.faq_eyebrow') }}</span>
            <h2>{{ __('store.home.faq_title') }} <em>{{ __('store.home.faq_title_em') }}</em></h2>
        </div>

        <div class="mq-faq-list mq-faq-accordion">
            @foreach (['q1', 'q2', 'q3', 'q4', 'q5', 'q6'] as $key)
                <details class="mq-faq-item">
                    <summary>
                        <span>{{ __('store.faq.'.$key) }}</span>
                        <i class="mq-faq-chevron" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </i>
                    </summary>
                    <p>{{ __('store.faq.a'.substr($key, 1)) }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="mq-section" style="padding-top:0">
    <div class="mq-container">
        <div class="mq-panel mq-home-loyalty">
            <div>
                <span class="mq-eyebrow">{{ __('store.home.loyalty_eyebrow') }}</span>
                <h2 style="margin:.4rem 0 .55rem">{{ __('store.home.loyalty_title') }}</h2>
                <p style="margin:0;color:var(--mq-muted);max-width:520px">{{ __('store.home.loyalty_text') }}</p>
            </div>
            <div class="mq-hero-actions">
                <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-primary">{{ __('store.home.loyalty_details') }}</a>
                <a href="{{ route('store.profile') }}" class="mq-btn mq-btn-ghost">{{ __('store.home.my_wallet') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="mq-section mq-feature-banner-wrap" style="padding-top:1.5rem">
    <div class="mq-container">
        <article class="mq-feature-banner">
            <div class="mq-feature-banner-copy">
                <span class="mq-feature-pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                    {{ __('store.home.banner_pill') }}
                </span>
                <h2>{{ __('store.home.banner_title') }}</h2>
                <p>{{ __('store.home.banner_text') }}</p>
                <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.home.banner_cta') }}</a>
                <div class="mq-feature-trust">
                    <span class="star" aria-hidden="true">★</span>
                    <span>{{ __('store.home.banner_trust') }}</span>
                </div>
            </div>
            <div class="mq-feature-banner-media">
                <img
                    src="{{ asset('store/img/feature-socket.webp') }}"
                    alt="{{ __('store.categories.sockets') }}"
                    width="1000"
                    height="1600"
                    loading="lazy"
                >
            </div>
        </article>
    </div>
</section>
@endsection
