@extends('store.layouts.app')

@section('title', __('store.home.title'))
@section('meta_description', __('store.meta_description'))

@section('content')
@php
    $locale = app()->getLocale();
    $primaryBanner = $banners->first();
@endphp

<section class="mq-hero mq-hero-calm" aria-label="{{ __('store.home.title') }}">
    <div class="mq-hero-media">
        @if ($primaryBanner && $primaryBanner->imageUrl())
            <img src="{{ $primaryBanner->imageUrl() }}" alt="{{ $primaryBanner->title_ar ?: __('store.brand') }}">
        @else
            <img src="{{ asset('store/img/hero-switches.jpg') }}" alt="{{ __('store.brand') }}">
        @endif
    </div>
    <div class="mq-hero-glow" aria-hidden="true"></div>

    <div class="mq-container mq-hero-content">
        <h1>{{ ($primaryBanner ? ($locale === 'ar' ? $primaryBanner->title_ar : $primaryBanner->title_en) : null) ?: __('store.home.hero_title') }}</h1>
        <p class="mq-hero-slogan-en">{{ __('store.home.hero_slogan_en') }}</p>
        <p>{{ __('store.home.hero_text') }}</p>
        <div class="mq-hero-actions">
            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.home.shop_now') }}</a>
            <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">{{ __('store.home.loyalty_cta') }}</a>
        </div>
    </div>
</section>

<section class="mq-features mq-features-slim">
    <div class="mq-container mq-features-grid">
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
            </div>
            <strong>{{ __('store.home.feature_shipping') }}</strong>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-3z"/></svg>
            </div>
            <strong>{{ __('store.home.feature_quality') }}</strong>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
            </div>
            <strong>{{ __('store.home.feature_points') }}</strong>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14v3a3 3 0 0 0 3 3h1"/><path d="M20 14v3a3 3 0 0 1-3 3h-1"/><path d="M8 21h8"/><path d="M7 4h10a2 2 0 0 1 2 2v5H5V6a2 2 0 0 1 2-2z"/></svg>
            </div>
            <strong>{{ __('store.home.feature_support') }}</strong>
        </div>
    </div>
</section>

{{-- Luxury Category Showcase --}}
<section class="mq-home-categories-section" aria-label="{{ __('store.home.product_types') }}">
    <div class="mq-container">
        <div class="mq-section-head mq-cat-section-head">
            <div class="mq-section-head-info">
                <span class="mq-eyebrow">{{ __('store.shop.sections') }}</span>
                <h2>{{ $locale === 'ar' ? 'تصفح المنتجات حسب القسم' : 'Explore by Category' }}</h2>
                <p>{{ $locale === 'ar' ? 'تشكيلة متكاملة من أرقى المنتجات والحلول الكهربائية المعتمدة' : 'A curated range of premium certified electrical equipment and solutions' }}</p>
            </div>
            <a href="{{ route('store.shop') }}" class="mq-cat-view-all">
                <span>{{ __('store.categories.all') }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="{{ $locale === 'ar' ? 'M19 12H5M12 19l-7-7 7-7' : 'M5 12h14M12 5l7 7-7 7' }}"/>
                </svg>
            </a>
        </div>

        <div class="mq-cat-showcase-grid" role="list">
            <a href="{{ route('store.shop') }}" class="mq-cat-card is-all" role="listitem">
                <div class="mq-cat-card-media">
                    <span class="mq-cat-card-icon">
                        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                    </span>
                </div>
                <div class="mq-cat-card-content">
                    <h3 class="mq-cat-card-title">{{ __('store.categories.all') }}</h3>
                    <span class="mq-cat-card-count">{{ $categories->sum('products_count') }} {{ __('store.common.products') }}</span>
                </div>
                <span class="mq-cat-card-arrow" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="{{ $locale === 'ar' ? 'M15 18l-6-6 6-6' : 'M9 18l6-6-6-6' }}"/>
                    </svg>
                </span>
            </a>
            @foreach ($categories as $cat)
                @php
                    $catName = $locale === 'ar' ? $cat->name_ar : $cat->name_en;
                @endphp
                <a href="{{ route('store.shop', ['category' => $cat->id]) }}" class="mq-cat-card" role="listitem">
                    <div class="mq-cat-card-media">
                        @if ($cat->hasImage())
                            <img src="{{ $cat->image_url }}" alt="{{ $catName }}" class="mq-cat-card-img" loading="lazy">
                        @else
                            <span class="mq-cat-card-icon">
                                {!! $cat->icon ?? '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="3" width="10" height="14" rx="2"/><path d="M10 7v4M14 7v4M9 21h6"/></svg>' !!}
                            </span>
                        @endif
                    </div>
                    <div class="mq-cat-card-content">
                        <h3 class="mq-cat-card-title">{{ $catName }}</h3>
                        <span class="mq-cat-card-count">{{ $cat->products_count }} {{ __('store.common.products') }}</span>
                    </div>
                    <span class="mq-cat-card-arrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="{{ $locale === 'ar' ? 'M15 18l-6-6 6-6' : 'M9 18l6-6-6-6' }}"/>
                        </svg>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="mq-section mq-section-airy">
    <div class="mq-container">
        <div class="mq-section-head">
            <h2>{{ __('store.home.selected_products') }}</h2>
            <p>{{ __('store.home.selected_lead') }}</p>
        </div>

        <div class="mq-products">
            @include('store.partials.products', ['products' => $featuredProducts, 'enhanced' => true])
        </div>

        <div style="text-align:center;margin-top:2rem;">
            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost">{{ __('store.footer.all_products') }} →</a>
        </div>
    </div>
</section>

<section class="mq-section mq-section-airy" style="padding-top:0">
    <div class="mq-container">
        <div class="mq-panel mq-home-loyalty">
            <div>
                <span class="mq-eyebrow">{{ __('store.home.loyalty_eyebrow') }}</span>
                <h2 style="margin:.4rem 0 .55rem">{{ __('store.home.loyalty_title') }}</h2>
                <p style="margin:0;color:var(--mq-muted);max-width:480px">{{ __('store.home.loyalty_text') }}</p>
            </div>
            <div class="mq-hero-actions">
                <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-primary">{{ __('store.home.loyalty_details') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
