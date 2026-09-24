@extends('store.layouts.app')

@section('title', __('store.shop.title'))

@section('content')
@php
    $locale = app()->getLocale();
    $activeCategory = $categories->firstWhere('id', $activeCategoryId);
    $categoryTitle = $activeCategory ? ($locale === 'ar' ? $activeCategory->name_ar : $activeCategory->name_en) : __('store.shop.heading');
    $categoryDesc = $activeCategory 
        ? ($locale === 'ar' 
            ? ($activeCategory->description_ar ?: 'تشكيلة مميزة من ' . $activeCategory->name_ar . ' بأعلى معايير الجودة والضمان.') 
            : ($activeCategory->description_en ?: 'Selected range of ' . $activeCategory->name_en . ' with trusted quality.'))
        : __('store.shop.lead');
@endphp

{{-- Premium Eye-Friendly Category Header --}}
<section class="mq-shop-hero" aria-label="{{ $categoryTitle }}">
    <div class="mq-shop-hero-glow" aria-hidden="true"></div>
    <div class="mq-container mq-shop-hero-inner">
        <div class="mq-breadcrumb mq-breadcrumb-on-dark">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.shop') }}">{{ __('store.shop.breadcrumb') }}</a>
            @if ($activeCategory)
                <span class="sep">/</span>
                <span class="is-current">{{ $locale === 'ar' ? $activeCategory->name_ar : $activeCategory->name_en }}</span>
            @endif
        </div>

        <div class="mq-shop-hero-copy">
            <div class="mq-shop-hero-info">
                <div class="mq-shop-badge-row">
                    <span class="mq-eyebrow">{{ __('store.shop.eyebrow') }}</span>
                    @if ($activeCategory)
                        <span class="mq-hero-pill-badge">{{ $products->total() }} {{ __('store.common.products') }}</span>
                    @endif
                </div>
                <h1>{{ $categoryTitle }}</h1>
                <p>{{ $categoryDesc }}</p>
            </div>

            <div class="mq-shop-hero-actions">
                <form class="mq-shop-search" action="{{ route('store.shop') }}" method="get" role="search">
                    @if (request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('store.search.shop_placeholder') }}" aria-label="{{ __('store.nav.search') }}">
                    <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.search.button') }}</button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Sleek Category Pills Carousel --}}
<section class="mq-shop-cats" aria-label="{{ __('store.shop.sections') }}">
    <div class="mq-container">
        <div class="mq-cat-strip" role="list">
            <a href="{{ route('store.shop', request()->except(['category', 'page'])) }}" 
               class="mq-cat-pill {{ empty($activeCategoryId) ? 'is-active' : '' }}" 
               role="listitem">
                <span class="mq-pill-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </span>
                <span class="mq-pill-label">{{ __('store.categories.all') }}</span>
            </a>
            @foreach ($categories as $cat)
                @php
                    $isCatActive = (int) $activeCategoryId === $cat->id;
                    $cName = $locale === 'ar' ? $cat->name_ar : $cat->name_en;
                @endphp
                <a href="{{ route('store.shop', array_merge(request()->except(['category', 'page']), ['category' => $cat->id])) }}" 
                   class="mq-cat-pill {{ $isCatActive ? 'is-active' : '' }}" 
                   role="listitem">
                    <span class="mq-pill-icon">
                        @if ($cat->hasImage())
                            <img src="{{ $cat->image_url }}" alt="{{ $cName }}" class="mq-pill-img" loading="lazy">
                        @else
                            {!! $cat->icon ?? '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="3" width="10" height="14" rx="2"/><path d="M10 7v4M14 7v4M9 21h6"/></svg>' !!}
                        @endif
                    </span>
                    <span class="mq-pill-label">{{ $cName }}</span>
                    <span class="mq-pill-badge">{{ $cat->products_count }}</span>
                </a>
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

                <form action="{{ route('store.shop') }}" method="GET" class="mq-panel mq-filters-panel">
                    @if (request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    @if (request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.sections') }}</strong>
                        <label class="{{ empty($activeCategoryId) ? 'is-selected' : '' }}">
                            <input type="radio" name="category" value="" {{ empty($activeCategoryId) ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>{{ __('store.categories.all') }}</span>
                        </label>
                        @foreach ($categories as $cat)
                            <label class="{{ (int) $activeCategoryId === $cat->id ? 'is-selected' : '' }}">
                                <input type="radio" name="category" value="{{ $cat->id }}" {{ (int) $activeCategoryId === $cat->id ? 'checked' : '' }} onchange="this.form.submit()">
                                <span>{{ $locale === 'ar' ? $cat->name_ar : $cat->name_en }}</span>
                                <em>{{ $cat->products_count }}</em>
                            </label>
                        @endforeach
                    </div>

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.price_range') }}</strong>
                        <label>
                            <input type="radio" name="price_range" value="" {{ !request('price_range') ? 'checked' : '' }} onchange="this.form.submit()">
                            {{ __('store.shop.all') }}
                        </label>
                        <label>
                            <input type="radio" name="price_range" value="under_200" {{ request('price_range') === 'under_200' ? 'checked' : '' }} onchange="this.form.submit()">
                            {{ __('store.shop.under_200') }}
                        </label>
                        <label>
                            <input type="radio" name="price_range" value="between_200_500" {{ request('price_range') === 'between_200_500' ? 'checked' : '' }} onchange="this.form.submit()">
                            {{ __('store.shop.between_200_500') }}
                        </label>
                        <label>
                            <input type="radio" name="price_range" value="over_500" {{ request('price_range') === 'over_500' ? 'checked' : '' }} onchange="this.form.submit()">
                            {{ __('store.shop.over_500') }}
                        </label>
                    </div>

                    <div class="mq-filter-group">
                        <strong>{{ __('store.shop.availability') }}</strong>
                        <label>
                            <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                            {{ __('store.shop.in_stock') }}
                        </label>
                    </div>

                    <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost mq-btn-block" style="text-align:center;">{{ __('store.common.reset_filters') }}</a>
                </form>
            </aside>

            <div class="mq-shop-main">
                <div class="mq-shop-toolbar">
                    <div class="mq-shop-toolbar-start">
                        <button type="button" class="mq-btn mq-btn-ghost mq-filters-open" data-mq-filters-open>
                            {{ __('store.common.filter') }}
                        </button>
                        <div>
                            <strong>{{ $products->total() }} {{ __('store.common.products') }}</strong>
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
                        <form action="{{ route('store.shop') }}" method="GET" class="mq-sort-field">
                            @foreach(request()->except('sort') as $key => $val)
                                @if(is_array($val))
                                    @foreach($val as $subVal)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                @endif
                            @endforeach
                            <span>{{ __('store.common.sort_by') }}</span>
                            <select name="sort" aria-label="{{ __('store.common.sort_by') }}" onchange="this.form.submit()">
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('store.shop.sort_newest') }}</option>
                                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>{{ __('store.shop.sort_price_asc') }}</option>
                                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>{{ __('store.shop.sort_price_desc') }}</option>
                                <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>{{ __('store.shop.sort_best') }}</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if (request('q') || request('category') || request('price_range') || request('in_stock'))
                    <div class="mq-active-filters" aria-label="{{ __('store.common.filter') }}">
                        @if ($activeCategory)
                            <span class="mq-chip">{{ $locale === 'ar' ? $activeCategory->name_ar : $activeCategory->name_en }} 
                                <a href="{{ route('store.shop', request()->except('category')) }}" aria-label="{{ __('store.common.remove') }}">×</a>
                            </span>
                        @endif
                        @if (request('q'))
                            <span class="mq-chip">"{{ request('q') }}"
                                <a href="{{ route('store.shop', request()->except('q')) }}" aria-label="{{ __('store.common.remove') }}">×</a>
                            </span>
                        @endif
                        @if (request('in_stock'))
                            <span class="mq-chip">{{ __('store.shop.in_stock') }}
                                <a href="{{ route('store.shop', request()->except('in_stock')) }}" aria-label="{{ __('store.common.remove') }}">×</a>
                            </span>
                        @endif
                        <a href="{{ route('store.shop') }}" class="mq-chip-clear">{{ __('store.common.clear_all') }}</a>
                    </div>
                @endif

                <div class="mq-products mq-shop-grid" data-mq-products>
                    @include('store.partials.products', ['products' => $products, 'enhanced' => true, 'recommendedProducts' => $recommendedProducts ?? collect()])
                </div>

                @if ($products->hasPages())
                    <nav class="mq-pagination" aria-label="{{ __('store.shop.pages') }}">
                        @if ($products->onFirstPage())
                            <span class="mq-page-btn is-disabled" aria-disabled="true">{{ __('store.common.previous') }}</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" class="mq-page-btn" aria-label="{{ __('store.common.previous') }}">{{ __('store.common.previous') }}</a>
                        @endif

                        @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                            <a href="{{ $url }}" class="mq-page-btn {{ $page == $products->currentPage() ? 'is-active' : '' }}" {{ $page == $products->currentPage() ? 'aria-current="page"' : '' }}>{{ $page }}</a>
                        @endforeach

                        @if ($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" class="mq-page-btn" aria-label="{{ __('store.common.next') }}">{{ __('store.common.next') }}</a>
                        @else
                            <span class="mq-page-btn is-disabled" aria-disabled="true">{{ __('store.common.next') }}</span>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="mq-filters-backdrop" data-mq-filters-close hidden></div>
@endsection
