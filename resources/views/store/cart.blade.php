@extends('store.layouts.app')

@section('title', __('store.cart.title'))

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.cart.breadcrumb') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.cart.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.cart.lead') }}</p>

        @if ($cart->items->isEmpty())
            <div class="mq-panel" style="text-align:center;padding:4rem 1.5rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.5" style="margin-bottom:1rem;" aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>
                </svg>
                <h2 style="font-size:1.3rem;margin-bottom:.5rem;">{{ __('store.cart.empty_title', [], $locale) ?: 'سلة التسوق فارغة حالياً' }}</h2>
                <p style="color:var(--mq-muted);max-width:400px;margin:0 auto 1.5rem;">{{ __('store.cart.empty_text', [], $locale) ?: 'تصفح تشكيلتنا المميزة من الأدوات والمفاتيح الكهربائية وأضف منتجاتك المفضلة إلى السلة.' }}</p>
                <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.common.shop_now') }}</a>
            </div>
        @else
            <div class="mq-cart-layout">
                <div class="mq-panel" style="overflow:auto">
                    <table class="mq-table">
                        <thead>
                            <tr>
                                <th>{{ __('store.cart.product') }}</th>
                                <th>{{ __('store.common.price') }}</th>
                                <th>{{ __('store.common.qty') }}</th>
                                <th>{{ __('store.common.total') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart->items as $item)
                                @php
                                    $prod = $item->product;
                                    $name = $prod ? ($locale === 'ar' ? $prod->name_ar : $prod->name_en) : 'Product #' . $item->product_id;
                                    $catName = $prod?->category ? ($locale === 'ar' ? $prod->category->name_ar : $prod->category->name_en) : '';
                                    $unitPrice = (float) $item->unit_price;
                                    $itemTotal = $unitPrice * $item->quantity;
                                    $points = (int) max(5, floor($unitPrice / 2));
                                    
                                    $thumb = null;
                                    if ($prod) {
                                        if ($prod->image_path) $thumb = asset('storage/' . $prod->image_path);
                                        elseif ($prod->thumbnail) $thumb = asset('storage/' . $prod->thumbnail->path);
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="mq-cart-item">
                                            <div class="mq-cart-thumb" style="background:#1b2434;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:6px;width:56px;height:56px;">
                                                @if ($thumb)
                                                    <img src="{{ $thumb }}" alt="{{ $name }}" style="width:100%;height:100%;object-fit:cover;">
                                                @else
                                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.4"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                                                @endif
                                            </div>
                                            <div>
                                                @if ($prod)
                                                    <a href="{{ route('store.product', $prod->id) }}" style="color:inherit;text-decoration:none;">
                                                        <strong>{{ $name }}</strong>
                                                    </a>
                                                @else
                                                    <strong>{{ $name }}</strong>
                                                @endif
                                                @if (!empty($item->option_label))
                                                    <div style="margin-top:.2rem;">
                                                        <span style="display:inline-block;padding:.15rem .45rem;background:rgba(197,160,89,.15);border:1px solid rgba(197,160,89,.35);border-radius:4px;font-size:.76rem;color:var(--mq-gold);font-weight:600;">
                                                            {{ $item->option_label }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div style="color:var(--mq-muted);font-size:.85rem;margin-top:.15rem;">
                                                    {{ $catName }} · +{{ $points }} {{ __('store.common.points') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($unitPrice, 2) }} {{ __('store.common.egp') }}</td>
                                    <td>
                                        <form action="{{ route('store.cart.update') }}" method="POST" class="mq-cart-qty-form" style="display:inline-flex;align-items:center;">
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                                            <div class="mq-qty">
                                                <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}" aria-label="Decrease">−</button>
                                                <span>{{ $item->quantity }}</span>
                                                <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" aria-label="Increase" {{ ($prod && $prod->stock_quantity > 0 && $item->quantity >= $prod->stock_quantity) ? 'disabled' : '' }}>+</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td><strong>{{ number_format($itemTotal, 2) }} {{ __('store.common.egp') }}</strong></td>
                                    <td style="text-align:end;">
                                        <form action="{{ route('store.cart.remove', $item->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mq-icon-btn" aria-label="{{ __('store.common.remove') }}" title="{{ __('store.common.remove') }}" style="color:var(--mq-muted);cursor:pointer;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Coupon Code Section -->
                    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--mq-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                        @if (!empty($summary['coupon_code']))
                            <div style="display:flex;align-items:center;gap:.75rem;">
                                <span style="background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:#22c55e;padding:.35rem .75rem;border-radius:6px;font-size:.88rem;">
                                    {{ __('store.cart.coupon_active') ?? 'كود الخصم مفعل' }}: <strong>{{ $summary['coupon_code'] }}</strong>
                                </span>
                                <form action="{{ route('store.cart.coupon.remove') }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mq-btn mq-btn-ghost" style="padding:.3rem .6rem;font-size:.82rem;">{{ __('store.common.remove') }}</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('store.cart.coupon.apply') }}" method="POST" style="display:flex;gap:.5rem;max-width:340px;width:100%;">
                                @csrf
                                <input type="text" name="code" placeholder="{{ __('store.cart.coupon_placeholder') ?? 'أدخل كود الخصم (مثل: WELCOME)' }}" required style="flex:1;padding:.5rem .75rem;background:rgba(255,255,255,.05);border:1px solid var(--mq-border);border-radius:6px;color:inherit;">
                                <button type="submit" class="mq-btn mq-btn-primary" style="padding:.5rem 1rem;">{{ __('store.cart.apply_coupon') ?? 'تطبيق' }}</button>
                            </form>
                        @endif

                        <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost" style="font-size:.88rem;">{{ __('store.common.continue_shopping') }}</a>
                    </div>
                </div>

                <aside class="mq-panel">
                    <h3 class="mq-side-title">{{ __('store.cart.summary') }}</h3>
                    <div class="mq-summary-row">
                        <span>{{ __('store.common.subtotal') }}</span>
                        <span>{{ number_format($summary['subtotal'], 2) }} {{ __('store.common.egp') }}</span>
                    </div>

                    @if ($summary['discount'] > 0)
                        <div class="mq-summary-row" style="color:#22c55e;">
                            <span>{{ __('store.cart.discount') ?? 'الخصم' }}</span>
                            <span>−{{ number_format($summary['discount'], 2) }} {{ __('store.common.egp') }}</span>
                        </div>
                    @endif

                    <div class="mq-summary-row">
                        <span>{{ __('store.common.shipping') }}</span>
                        <span>
                            @if ($summary['shipping'] <= 0)
                                <em style="color:#22c55e;font-style:normal;">{{ __('store.cart.free_shipping') ?? 'شحن مجاني' }}</em>
                            @else
                                {{ number_format($summary['shipping'], 2) }} {{ __('store.common.egp') }}
                            @endif
                        </span>
                    </div>

                    <div class="mq-summary-row total">
                        <span>{{ __('store.common.total') }}</span>
                        <span>{{ number_format($summary['total'], 2) }} {{ __('store.common.egp') }}</span>
                    </div>

                    <a href="{{ route('store.checkout') }}" class="mq-btn mq-btn-primary mq-btn-block" style="margin-top:1.25rem;">{{ __('store.cart.checkout') }}</a>
                </aside>
            </div>
        @endif
    </div>
</section>
@endsection
