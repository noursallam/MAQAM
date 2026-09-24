@extends('store.layouts.app')

@php
    $locale = app()->getLocale();
    $name = $locale === 'ar' ? ($product->name_ar ?: $product->name_en) : ($product->name_en ?: $product->name_ar);
    $desc = $locale === 'ar' ? ($product->description_ar ?: $product->description_en) : ($product->description_en ?: $product->description_ar);
    $catName = $product->category ? ($locale === 'ar' ? $product->category->name_ar : $product->category->name_en) : __('store.categories.all');
    $points = (int) max(10, floor((float) $product->price / 2));
    
    $mainImg = null;
    if (!empty($product->image_path)) {
        $mainImg = asset('storage/' . $product->image_path);
    } elseif ($product->thumbnail && !empty($product->thumbnail->path)) {
        $mainImg = asset('storage/' . $product->thumbnail->path);
    } elseif ($product->images && $product->images->isNotEmpty()) {
        $mainImg = asset('storage/' . $product->images->first()->path);
    }
@endphp

@section('title', $name . ' — ' . __('store.store_name'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.shop') }}">{{ __('store.nav.shop') }}</a>
            @if ($product->category)
                <span class="sep">/</span>
                <a href="{{ route('store.shop', ['category' => $product->category_id]) }}">{{ $catName }}</a>
            @endif
            <span class="sep">/</span>
            <span>{{ $name }}</span>
        </div>

        <div class="mq-product-layout">
            <div class="mq-product-gallery" style="background:#1b2434;display:flex;align-items:center;justify-content:center;min-height:360px;border-radius:12px;overflow:hidden;border:1px solid var(--mq-border);">
                @if ($mainImg)
                    <img id="mqMainProductImg" src="{{ $mainImg }}" alt="{{ $name }}" style="width:100%;max-height:420px;object-fit:contain;padding:1rem;">
                @else
                    <svg width="140" height="140" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.1" aria-hidden="true">
                        <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
                    </svg>
                @endif
            </div>

            <div class="mq-product-info">
                @if ($product->category)
                    <span class="mq-eyebrow">{{ $catName }}</span>
                @endif
                <h1>{{ $name }}</h1>
                <div class="mq-product-meta">
                    <span>{{ __('store.common.rating') }}: 4.9 / 5</span>
                    <span>{{ __('store.common.available') }}: {{ $product->stock_quantity }} {{ __('store.product.pieces') }}</span>
                    <span>{{ __('store.product.sku') }}: #{{ $product->sku ?: $product->id }}</span>
                </div>
                <div class="mq-product-price">{{ number_format((float) $product->price, 2) }} {{ __('store.common.egp') }}</div>
                
                @if ($desc)
                    <p style="color:var(--mq-muted);margin:0 0 1rem;line-height:1.6;">{{ $desc }}</p>
                @endif

                <div class="mq-loyalty-inline">
                    <strong>{{ __('store.product.loyalty_badge') }}</strong>
                    <span>+{{ $points }} {{ __('store.common.points') }} — {{ __('store.product.loyalty_hint') }}</span>
                </div>

                <form action="{{ route('store.cart.add') }}" method="POST" id="mqProductForm">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($product->colors && $product->colors->isNotEmpty())
                        <div style="margin-bottom:1rem;">
                            <label style="display:block;margin-bottom:.4rem;font-weight:600;font-size:.9rem;">{{ __('store.product.colors') ?? 'اللون' }}:</label>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                @foreach ($product->colors as $i => $color)
                                    <label style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .75rem;background:rgba(255,255,255,.05);border:1px solid var(--mq-border);border-radius:6px;cursor:pointer;">
                                        <input type="radio" name="color" value="{{ $color->name }}" {{ $i === 0 ? 'checked' : '' }}>
                                        @if ($color->hex)
                                            <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $color->hex }};border:1px solid rgba(255,255,255,.2);"></span>
                                        @endif
                                        <span>{{ $color->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($product->options && $product->options->isNotEmpty())
                        <div style="margin-bottom:1rem;">
                            <label style="display:block;margin-bottom:.4rem;font-weight:600;font-size:.9rem;">{{ __('store.product.options') ?? 'المقاس / الخيار' }}:</label>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                @foreach ($product->options as $i => $opt)
                                    <label style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .75rem;background:rgba(255,255,255,.05);border:1px solid var(--mq-border);border-radius:6px;cursor:pointer;">
                                        <input type="radio" name="option" value="{{ $opt->value }}" {{ $i === 0 ? 'checked' : '' }}>
                                        <span>{{ $opt->name }}: {{ $opt->value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
                        <div class="mq-qty" aria-label="{{ __('store.common.qty') }}">
                            <button type="button" onclick="var el=document.getElementById('mqProductQty'); var v=parseInt(el.value)||1; if(v>1) el.value=v-1;">−</button>
                            <input type="number" name="quantity" id="mqProductQty" value="1" min="1" max="{{ max(1, $product->stock_quantity) }}" style="width:50px;text-align:center;background:transparent;border:none;color:inherit;font-weight:700;">
                            <button type="button" onclick="var el=document.getElementById('mqProductQty'); var v=parseInt(el.value)||1; if(v<{{ max(1, $product->stock_quantity) }}) el.value=v+1;">+</button>
                        </div>
                    </div>

                    <div class="mq-product-actions">
                        @if ($product->stock_quantity > 0)
                            <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.product.add_cart') }}</button>
                            <button type="submit" formaction="{{ route('store.cart.add') }}" name="buy_now" value="1" class="mq-btn mq-btn-ghost">{{ __('store.product.buy_now') }}</button>
                        @else
                            <button type="button" class="mq-btn mq-btn-ghost" disabled style="opacity:.6;">{{ __('store.shop.out_of_stock') ?? 'المنتج غير متوفر حالياً' }}</button>
                        @endif
                    </div>
                </form>

                <div class="mq-tabs" role="tablist" style="margin-top:2rem;">
                    <button type="button" class="is-active">{{ __('store.product.description') }}</button>
                </div>
                <div class="mq-panel">
                    <p style="margin:0;color:var(--mq-muted);line-height:1.7;">
                        {{ $desc ?: __('store.product.panel_text') }}
                    </p>
                </div>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <div style="margin-top:4rem;">
                <h2 style="font-size:1.4rem;margin-bottom:1.5rem;">{{ __('store.product.related') ?? 'منتجات ذات صلة' }}</h2>
                <div class="mq-products">
                    @include('store.partials.products', ['products' => $relatedProducts, 'enhanced' => true])
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
