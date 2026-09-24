@extends('store.layouts.app')

@section('title', __('store.order.confirmation_title') ?? 'تأكيد الطلب')

@section('content')
@php
    $locale = app()->getLocale();
    $statusColors = [
        'new' => 'background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);',
        'processing' => 'background:rgba(234,179,8,.15);color:#facc15;border:1px solid rgba(234,179,8,.3);',
        'shipped' => 'background:rgba(168,85,247,.15);color:#c084fc;border:1px solid rgba(168,85,247,.3);',
        'delivered' => 'background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.3);',
        'cancelled' => 'background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);',
    ];
    $statusLabels = [
        'new' => 'تم استلام الطلب',
        'processing' => 'جاري تجهيز الطلب',
        'shipped' => 'تم الشحن مع مندوب التوصيل',
        'delivered' => 'تم التسليم بنجاح',
        'cancelled' => 'ملغي',
    ];
@endphp

<section class="mq-page">
    <div class="mq-container" style="max-width:820px;">
        <div class="mq-panel" style="text-align:center;padding:3rem 1.5rem 2rem;margin-bottom:2rem;">
            <div style="width:64px;height:64px;margin:0 auto 1.25rem;border-radius:50%;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);display:flex;align-items:center;justify-content:center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.2">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <h1 style="font-size:1.8rem;margin:0 0 .5rem;">شكراً لك! تم تسجيل طلبك بنجاح</h1>
            <p style="color:var(--mq-muted);margin:0 0 1rem;font-size:1.05rem;">
                رقم الطلب: <strong style="color:var(--mq-gold);letter-spacing:1px;">#{{ $order->order_number }}</strong>
            </p>
            <div style="display:inline-block;padding:.4rem 1.1rem;border-radius:20px;font-size:.9rem;font-weight:600;{{ $statusColors[$order->status] ?? '' }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </div>
        </div>

        <div class="mq-panel" style="margin-bottom:2rem;">
            <h3 class="mq-side-title">تفاصيل المنتجات في الطلب</h3>
            <table class="mq-table" style="margin-bottom:1.5rem;">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>السعر</th>
                        <th>الكمية</th>
                        <th style="text-align:end;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        @php
                            $prod = $item->product;
                            $name = $prod ? ($locale === 'ar' ? $prod->name_ar : $prod->name_en) : 'Product #' . $item->product_id;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $name }}</strong>
                            </td>
                            <td>{{ number_format($item->unit_price, 2) }} {{ __('store.common.egp') }}</td>
                            <td>× {{ $item->quantity }}</td>
                            <td style="text-align:end;font-weight:600;">{{ number_format($item->subtotal, 2) }} {{ __('store.common.egp') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="border-top:1px solid var(--mq-border);padding-top:1rem;">
                <div class="mq-summary-row">
                    <span>{{ __('store.common.subtotal') }}</span>
                    <span>{{ number_format($order->subtotal, 2) }} {{ __('store.common.egp') }}</span>
                </div>
                @if ($order->discount > 0)
                    <div class="mq-summary-row" style="color:#22c55e;">
                        <span>{{ __('store.cart.discount') ?? 'الخصم' }}</span>
                        <span>−{{ number_format($order->discount, 2) }} {{ __('store.common.egp') }}</span>
                    </div>
                @endif
                <div class="mq-summary-row">
                    <span>{{ __('store.common.shipping') }}</span>
                    <span>{{ $order->shipping_cost > 0 ? number_format($order->shipping_cost, 2) . ' ' . __('store.common.egp') : 'مجاني' }}</span>
                </div>
                <div class="mq-summary-row total">
                    <span>{{ __('store.common.total') }}</span>
                    <span style="color:var(--mq-gold);">{{ number_format($order->total_amount, 2) }} {{ __('store.common.egp') }}</span>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.5rem;margin-bottom:2.5rem;">
            <div class="mq-panel">
                <h3 class="mq-side-title">عنوان الشحن والتوصيل</h3>
                @if ($order->shippingAddress)
                    <p style="margin:0 0 .35rem;"><strong>{{ $order->shippingAddress->recipient_name }}</strong></p>
                    <p style="margin:0 0 .35rem;color:var(--mq-muted);" dir="ltr">{{ $order->shippingAddress->phone }}</p>
                    <p style="margin:0 0 .35rem;color:var(--mq-muted);">{{ $order->shippingAddress->governorate }} — {{ $order->shippingAddress->city }}</p>
                    <p style="margin:0;color:var(--mq-muted);">{{ $order->shippingAddress->address_line1 }}</p>
                @endif
            </div>

            <div class="mq-panel">
                <h3 class="mq-side-title">طريقة وحالة الدفع</h3>
                <p style="margin:0 0 .5rem;">
                    طريقة الدفع:
                    <strong>
                        @if ($order->payment_method === 'kashier')
                            بطاقة بنكية / محفظة (Kashier)
                        @elseif ($order->payment_method === 'cod')
                            الدفع عند الاستلام (COD)
                        @elseif ($order->payment_method === 'wallet')
                            نقاط محفظة الولاء
                        @else
                            {{ strtoupper($order->payment_method) }}
                        @endif
                    </strong>
                </p>
                <p style="margin:0;">
                    حالة الدفع:
                    @if ($order->payment_status === 'paid')
                        <span style="color:#22c55e;font-weight:700;">تم الدفع بنجاح ✓</span>
                    @else
                        <span style="color:#facc15;font-weight:700;">قيد الدفع / عند الاستلام</span>
                    @endif
                </p>
            </div>
        </div>

        <div style="text-align:center;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            @auth
                <a href="{{ route('store.profile') }}#orders" class="mq-btn mq-btn-primary">متابعة طلباتي في حسابي</a>
            @endauth
            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost">متابعة التسوق</a>
        </div>
    </div>
</section>
@endsection
