@extends('store.layouts.app')

@section('title', __('store.shop.title'))

@section('content')
@php
    $categories = [
        [
            'key' => 'all',
            'count' => 48,
            'active' => true,
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

<section class="mq-shop-hero" aria-label="{{ __('store.shop.heading') }}">
    <div class="mq-shop-hero-bg" aria-hidden="true"></div>
    <div class="mq-container mq-shop-hero-inner">
        <div class="mq-breadcrumb mq-breadcrumb-on-dark">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.shop.breadcrumb') }}</span>
        </div>
        <div class="mq-shop-hero-copy">
            <div>
                <span class="mq-eyebrow">{{ __('store.shop.eyebrow') }}</span>
                <h1>{{ __('store.shop.heading') }}</h1>
                <p>{{ __('store.shop.lead') }}</p>
            </div>
            <form class="mq-shop-search" action="{{ route('store.shop') }}" method="get" role="search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('store.search.shop_placeholder') }}" aria-label="{{ __('store.nav.search') }}">
                <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.search.button') }}</button>
            </form>
        </div>
    </div>
</section>

<section class="mq-shop-cats" aria-label="{{ __('store.shop.sections') }}">
    <div class="mq-container">
        <div class="mq-cat-strip" role="list">
            @foreach ($categories as $cat)
                <button type="button" class="mq-cat-item {{ !empty($cat['active']) ? 'is-active' : '' }}" role="listitem">
                    <span class="mq-cat-avatar" style="--cat-tone: {{ $cat['tone'] }}">
                        {!! $cat['icon'] !!}
                    </span>
                    <span class="mq-cat-label">{{ __('store.categories.'.$cat['key']) }}</span>
                    <em class="mq-cat-count">{{ $cat['count'] }} {{ __('store.common.product') }}</em>
                </button>
            @endforeach
        </div>
    </div>
</section>

<section class="mq-page mq-shop-page">
    <div class="mq-container">
        <div class="mq-shop-layout">
            <aside class="mq-filters" id="mqShopFilters" aria-label="{{ __('store.common.filter') }}">
                <div class="mq-filters-head">
                    <h2 class="mq-side-title">{{ __('store.shop.filter_results') }}</h2>
                    <button type="button" class="mq-filters-close" data-mq-filters-close aria-label="{{ __('store.shop.close_filters') }}">×</button>
                </div>

                <div class="mq-panel mq-filters-panel">
                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.sections') }}</strong>
                        @foreach ($categories as $i => $cat)
                            <label>
                                <input type="checkbox" {{ $i === 0 ? 'checked' : '' }}>
                                <span>{{ __('store.categories.'.$cat['key']) }}</span>
                                <em>{{ $cat['count'] }}</em>
                            </label>
                        @endforeach
                    </div>

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.price_range') }}</strong>
                        <div class="mq-price-range">
                            <input type="range" min="0" max="5000" value="3400" aria-label="{{ __('store.shop.price_range') }}">
                            <div class="mq-price-inputs">
                                <span>0 {{ __('store.common.egp') }}</span>
                                <span>500 {{ __('store.common.egp') }}</span>
                            </div>
                        </div>
                        <label><input type="radio" name="price" checked> {{ __('store.shop.all') }}</label>
                        <label><input type="radio" name="price"> {{ __('store.shop.under_200') }}</label>
                        <label><input type="radio" name="price"> {{ __('store.shop.between_200_500') }}</label>
                        <label><input type="radio" name="price"> {{ __('store.shop.over_500') }}</label>
                    </div>

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.availability') }}</strong>
                        <label><input type="checkbox" checked> {{ __('store.shop.in_stock') }}</label>
                        <label><input type="checkbox"> {{ __('store.shop.offers') }}</label>
                        <label><input type="checkbox"> {{ __('store.shop.new_arrivals') }}</label>
                    </div>

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.rating') }}</strong>
                        <label><input type="radio" name="rating" checked> {{ __('store.shop.all') }}</label>
                        <label><input type="radio" name="rating"> {{ __('store.shop.stars_4') }}</label>
                        <label><input type="radio" name="rating"> {{ __('store.shop.stars_3') }}</label>
                    </div>

                    <button type="button" class="mq-btn mq-btn-ghost mq-btn-block">{{ __('store.common.reset_filters') }}</button>
                </div>
            </aside>

            <div class="mq-shop-main">
                <div class="mq-shop-toolbar">
                    <div class="mq-shop-toolbar-start">
                        <button type="button" class="mq-btn mq-btn-ghost mq-filters-open" data-mq-filters-open>
                            {{ __('store.common.filter') }}
                        </button>
                        <div>
                            <strong>8 {{ __('store.common.products') }}</strong>
                            <span class="mq-shop-result-meta">{{ __('store.common.of') }} 48 {{ __('store.common.results') }}</span>
                        </div>
                    </div>

                    <div class="mq-shop-toolbar-end">
                        <div class="mq-view-toggle" role="group" aria-label="{{ __('store.shop.grid') }}">
                            <button type="button" class="is-active" data-mq-view="grid" aria-label="{{ __('store.shop.grid') }}" title="{{ __('store.shop.grid') }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </button>
                            <button type="button" data-mq-view="list" aria-label="{{ __('store.shop.list') }}" title="{{ __('store.shop.list') }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="5" width="18" height="3"/><rect x="3" y="10.5" width="18" height="3"/><rect x="3" y="16" width="18" height="3"/></svg>
                            </button>
                        </div>
                        <label class="mq-sort-field">
                            <span>{{ __('store.common.sort_by') }}</span>
                            <select aria-label="{{ __('store.common.sort_by') }}">
                                <option>{{ __('store.shop.sort_best') }}</option>
                                <option>{{ __('store.shop.sort_newest') }}</option>
                                <option>{{ __('store.shop.sort_price_asc') }}</option>
                                <option>{{ __('store.shop.sort_price_desc') }}</option>
                                <option>{{ __('store.shop.sort_rating') }}</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="mq-active-filters" aria-label="{{ __('store.common.filter') }}">
                    <span class="mq-chip">{{ __('store.categories.sockets') }} <button type="button" aria-label="{{ __('store.common.remove') }}">×</button></span>
                    <span class="mq-chip">{{ __('store.shop.in_stock') }} <button type="button" aria-label="{{ __('store.common.remove') }}">×</button></span>
                    <button type="button" class="mq-chip-clear">{{ __('store.common.clear_all') }}</button>
                </div>

                <div class="mq-products mq-shop-grid" data-mq-products>
                    @include('store.partials.products', ['enhanced' => true])
                </div>

                <nav class="mq-pagination" aria-label="{{ __('store.shop.pages') }}">
                    <button type="button" class="mq-page-btn" disabled aria-label="{{ __('store.common.previous') }}">{{ __('store.common.previous') }}</button>
                    <button type="button" class="mq-page-btn is-active" aria-current="page">1</button>
                    <button type="button" class="mq-page-btn">2</button>
                    <button type="button" class="mq-page-btn">3</button>
                    <button type="button" class="mq-page-btn" aria-label="{{ __('store.common.next') }}">{{ __('store.common.next') }}</button>
                </nav>
            </div>
        </div>
    </div>
</section>

<div class="mq-filters-backdrop" data-mq-filters-close hidden></div>
@endsection
