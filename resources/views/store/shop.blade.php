@extends('store.layouts.app')

@section('title', 'المتجر | مقام')

@section('content')
@php
    $categories = [
        [
            'name' => 'الكل',
            'count' => 48,
            'active' => true,
            'tone' => '#1b2434',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        ],
        [
            'name' => 'فيش وبرايز',
            'count' => 14,
            'tone' => '#243044',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="7" y="3" width="10" height="14" rx="2"/><path d="M10 7v4M14 7v4M9 21h6"/></svg>',
        ],
        [
            'name' => 'مفاتيح',
            'count' => 10,
            'tone' => '#1f2937',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="6" width="16" height="12" rx="2"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/></svg>',
        ],
        [
            'name' => 'كابلات وأسلاك',
            'count' => 8,
            'tone' => '#222b3b',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8c4 0 4 8 8 8s4-8 8-8"/><path d="M4 16c4 0 4-8 8-8s4 8 8 8"/></svg>',
        ],
        [
            'name' => 'إضاءة',
            'count' => 7,
            'tone' => '#1a2332',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18h6M10 21h4"/><path d="M8 10a4 4 0 1 1 8 0c0 2-1.5 3-2 4H10c-.5-1-2-2-2-4z"/></svg>',
        ],
        [
            'name' => 'قواطع ولوحات',
            'count' => 5,
            'tone' => '#182131',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>',
        ],
    ];
@endphp

<section class="mq-shop-hero" aria-label="مقدمة المتجر">
    <div class="mq-shop-hero-bg" aria-hidden="true"></div>
    <div class="mq-container mq-shop-hero-inner">
        <div class="mq-breadcrumb mq-breadcrumb-on-dark">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>المتجر</span>
        </div>
        <div class="mq-shop-hero-copy">
            <div>
                <span class="mq-eyebrow">مقام ستور</span>
                <h1>متجر الأدوات الكهربائية</h1>
                <p>فيش وبرايز ومفاتيح وكابلات وإضاءة — مع نظام ولاء ونقاط عبر مسح كود QR داخل كل منتج.</p>
            </div>
            <form class="mq-shop-search" role="search" onsubmit="return false;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" placeholder="ابحث عن منتج، فئة، أو ماركة..." aria-label="بحث في المتجر">
                <button type="submit" class="mq-btn mq-btn-primary">بحث</button>
            </form>
        </div>
    </div>
</section>

<section class="mq-shop-cats" aria-label="أقسام المتجر">
    <div class="mq-container">
        <div class="mq-cat-strip" role="list">
            @foreach ($categories as $cat)
                <button type="button" class="mq-cat-item {{ !empty($cat['active']) ? 'is-active' : '' }}" role="listitem">
                    <span class="mq-cat-avatar" style="--cat-tone: {{ $cat['tone'] }}">
                        {!! $cat['icon'] !!}
                    </span>
                    <span class="mq-cat-label">{{ $cat['name'] }}</span>
                    <em class="mq-cat-count">{{ $cat['count'] }} منتج</em>
                </button>
            @endforeach
        </div>
    </div>
</section>

<section class="mq-page mq-shop-page">
    <div class="mq-container">
        <div class="mq-shop-layout">
            <aside class="mq-filters" id="mqShopFilters" aria-label="تصفية المنتجات">
                <div class="mq-filters-head">
                    <h2 class="mq-side-title">تصفية النتائج</h2>
                    <button type="button" class="mq-filters-close" data-mq-filters-close aria-label="إغلاق التصفية">×</button>
                </div>

                <div class="mq-panel mq-filters-panel">
                    <div class="mq-filter-group">
                        <strong>الأقسام</strong>
                        @foreach ($categories as $i => $cat)
                            <label>
                                <input type="checkbox" {{ $i === 0 ? 'checked' : '' }}>
                                <span>{{ $cat['name'] }}</span>
                                <em>{{ $cat['count'] }}</em>
                            </label>
                        @endforeach
                    </div>

                    <div class="mq-filter-group">
                        <strong>نطاق السعر</strong>
                        <div class="mq-price-range">
                            <input type="range" min="0" max="5000" value="3400" aria-label="الحد الأقصى للسعر">
                            <div class="mq-price-inputs">
                                <span>٠ ج.م</span>
                                <span>٥٠٠ ج.م</span>
                            </div>
                        </div>
                        <label><input type="radio" name="price" checked> الكل</label>
                        <label><input type="radio" name="price"> أقل من ٢٠٠</label>
                        <label><input type="radio" name="price"> ٢٠٠ – ٥٠٠</label>
                        <label><input type="radio" name="price"> أكثر من ٥٠٠</label>
                    </div>

                    <div class="mq-filter-group">
                        <strong>التوفر</strong>
                        <label><input type="checkbox" checked> متوفر الآن</label>
                        <label><input type="checkbox"> عروض وتخفيضات</label>
                        <label><input type="checkbox"> وصل حديثًا</label>
                    </div>

                    <div class="mq-filter-group">
                        <strong>التقييم</strong>
                        <label><input type="radio" name="rating" checked> الكل</label>
                        <label><input type="radio" name="rating"> ٤ نجوم فأكثر</label>
                        <label><input type="radio" name="rating"> ٣ نجوم فأكثر</label>
                    </div>

                    <button type="button" class="mq-btn mq-btn-ghost mq-btn-block">إعادة ضبط الفلاتر</button>
                </div>
            </aside>

            <div class="mq-shop-main">
                <div class="mq-shop-toolbar">
                    <div class="mq-shop-toolbar-start">
                        <button type="button" class="mq-btn mq-btn-ghost mq-filters-open" data-mq-filters-open>
                            تصفية
                        </button>
                        <div>
                            <strong>٨ منتجات</strong>
                            <span class="mq-shop-result-meta">من أصل ٤٨ نتيجة</span>
                        </div>
                    </div>

                    <div class="mq-shop-toolbar-end">
                        <div class="mq-view-toggle" role="group" aria-label="طريقة العرض">
                            <button type="button" class="is-active" data-mq-view="grid" aria-label="عرض شبكي" title="شبكي">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </button>
                            <button type="button" data-mq-view="list" aria-label="عرض قائمة" title="قائمة">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="5" width="18" height="3"/><rect x="3" y="10.5" width="18" height="3"/><rect x="3" y="16" width="18" height="3"/></svg>
                            </button>
                        </div>
                        <label class="mq-sort-field">
                            <span>ترتيب حسب</span>
                            <select aria-label="ترتيب المنتجات">
                                <option>الأكثر مبيعًا</option>
                                <option>الأحدث</option>
                                <option>السعر: من الأقل</option>
                                <option>السعر: من الأعلى</option>
                                <option>الأعلى تقييمًا</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="mq-active-filters" aria-label="الفلاتر النشطة">
                    <span class="mq-chip">فيش وبرايز <button type="button" aria-label="إزالة">×</button></span>
                    <span class="mq-chip">متوفر الآن <button type="button" aria-label="إزالة">×</button></span>
                    <button type="button" class="mq-chip-clear">مسح الكل</button>
                </div>

                <div class="mq-products mq-shop-grid" data-mq-products>
                    @include('store.partials.products', ['enhanced' => true])
                </div>

                <nav class="mq-pagination" aria-label="صفحات المتجر">
                    <button type="button" class="mq-page-btn" disabled aria-label="السابق">السابق</button>
                    <button type="button" class="mq-page-btn is-active" aria-current="page">١</button>
                    <button type="button" class="mq-page-btn">٢</button>
                    <button type="button" class="mq-page-btn">٣</button>
                    <button type="button" class="mq-page-btn" aria-label="التالي">التالي</button>
                </nav>
            </div>
        </div>
    </div>
</section>

<div class="mq-filters-backdrop" data-mq-filters-close hidden></div>
@endsection
