@extends('admin.layouts.app')

@section('title', __('admin.orders.workspace').' '.$order->order_number)
@section('breadcrumbs')
<a href="{{ route('admin.orders.index') }}" class="hover:text-maqam-ink">{{ __('admin.orders.title') }}</a>
<span class="mx-1">/</span>
<span>{{ $order->order_number }}</span>
@endsection

@section('content')
@php
    $flow = ['new','processing','shipped','delivered'];
    $statusLabels = [
        'new' => __('admin.orders.new'),
        'processing' => __('admin.orders.processing'),
        'shipped' => __('admin.orders.shipped'),
        'delivered' => __('admin.orders.delivered'),
        'cancelled' => __('admin.orders.cancelled'),
        'refunded' => __('admin.orders.refunded'),
    ];
    $idx = array_search($order->status, $flow, true);
    $paymentMethod = strtolower($order->payment_method ?? 'credit');
    $paymentMethodIcons = [
        'credit' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
        'cod' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    ];
    $paymentMethodColors = [
        'credit' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'cod' => 'bg-orange-100 text-orange-700 border-orange-200',
    ];
    $paymentIcon = $paymentMethodIcons[$paymentMethod] ?? $paymentMethodIcons['credit'];
    $paymentColor = $paymentMethodColors[$paymentMethod] ?? $paymentMethodColors['credit'];
@endphp

{{-- Order Header --}}
<div class="mb-6 ui-panel">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#EAE7DF]">
                <svg class="h-6 w-6 text-maqam-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-maqam-ink">{{ $order->order_number }}</div>
                <div class="mt-1 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium uppercase {{ $paymentColor }}">
                        {!! $paymentIcon !!}
                        <span>{{ $order->payment_method }}</span>
                    </span>
                    <span class="text-xs text-maqam-muted">{{ $order->payment_status }}</span>
                    <span class="text-[#D8D4CB]">·</span>
                    <span class="text-xs text-maqam-muted">{{ $order->created_at?->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-2xl font-bold text-maqam-ink">{{ number_format($order->total_amount, 0) }} <span class="text-lg font-medium text-maqam-muted">ج.م</span></div>
            <span class="ui-badge {{ $statusColors[$order->status] ?? 'ui-badge-muted' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
        </div>
    </div>
</div>

{{-- Timeline --}}
<div class="mb-6 ui-panel">
    <h2 class="mb-4 ui-section-title ui-muted !text-sm">{{ __('admin.orders.timeline') }}</h2>
    <div class="flex flex-wrap items-center gap-2">
        @foreach($flow as $i => $st)
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold {{ $idx !== false && $i <= $idx ? 'bg-maqam-gold text-maqam-navy' : 'border border-[#D8D4CB] bg-[#F3F1EB] text-maqam-muted' }}">{{ $i+1 }}</span>
                <span class="text-sm {{ $order->status === $st ? 'font-semibold' : 'ui-muted' }}">{{ $statusLabels[$st] }}</span>
                @if($i < count($flow)-1)<span class="mx-1 h-px w-8 bg-[#D8D4CB]"></span>@endif
            </div>
        @endforeach
        @if(in_array($order->status, ['cancelled','refunded'], true))
            <span class="ms-4 ui-badge ui-badge-danger">{{ $statusLabels[$order->status] }}</span>
        @endif
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <div class="ui-card-static p-6">
            <h2 class="mb-4 ui-section-title">{{ __('admin.orders.items') }}</h2>
            <div class="space-y-3">
                @foreach($order->items as $item)
                    <div class="ui-row">
                        <div>
                            <div class="font-medium text-sm">{{ $item->product?->name_ar ?? $item->product?->name_en }}</div>
                            <div class="ui-muted mt-0.5">× {{ $item->quantity }} · {{ number_format($item->unit_price, 2) }}</div>
                        </div>
                        <div class="font-semibold text-sm">{{ number_format($item->subtotal, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @if($order->shippingAddress)
        <div class="ui-card-static p-6 text-sm">
            <h2 class="mb-2 ui-section-title">{{ __('admin.orders.shipping') }}</h2>
            <p>{{ $order->shippingAddress->recipient_name }} · {{ $order->shippingAddress->phone }}</p>
            <p class="ui-muted mt-1">{{ $order->shippingAddress->address_line1 }}, {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->governorate }}</p>
        </div>
        @endif
    </div>

    <div class="space-y-4">
        {{-- Payment & Delivery Info --}}
        <div class="ui-card-static p-6 text-sm">
            <div class="mb-4">
                <div class="mb-1 ui-muted">{{ __('admin.orders.payment') }}</div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium uppercase {{ $paymentColor }}">
                        {!! $paymentIcon !!}
                        <span>{{ $order->payment_method }}</span>
                    </span>
                    <span class="text-xs text-maqam-muted">{{ $order->payment_status }}</span>
                </div>
                @if(strtolower($order->payment_method ?? '') === 'cod')
                    <div class="mt-2 rounded-lg bg-orange-50 p-2 text-xs text-orange-700">
                        <svg class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="mr-1">Cash on Delivery - Collect payment upon delivery</span>
                    </div>
                @endif
            </div>

            <div class="mb-4 ui-divider pt-4">
                <div class="mb-1 ui-muted">{{ __('admin.orders.customer') }}</div>
                <div class="font-medium">{{ $order->user?->full_name }}</div>
                @if($order->user?->customer)
                    <a href="{{ route('admin.customers.show', $order->user->customer) }}" class="mt-1 inline-block text-xs text-maqam-gold-dark underline">{{ __('admin.customers.profile') }}</a>
                @endif
            </div>

            <div class="space-y-1 ui-divider pt-4">
                <div class="flex justify-between"><span>فرعي</span><span>{{ number_format($order->subtotal, 0) }} ج.م</span></div>
                <div class="flex justify-between"><span>{{ __('admin.orders.shipping') }}</span><span>{{ number_format($order->shipping_cost, 0) }} ج.م</span></div>
                <div class="flex justify-between"><span>خصم</span><span>{{ number_format($order->discount, 0) }} ج.م</span></div>
                @if($order->coupon_code || $order->reward)
                    <div class="flex justify-between text-sm">
                        <span>{{ __('admin.rewards.code') }}</span>
                        <span dir="ltr">
                            <code>{{ $order->coupon_code ?? $order->reward?->code }}</code>
                            @if($order->reward)
                                <span class="ui-muted">({{ __('admin.rewards.type_'.$order->reward->type) }})</span>
                            @endif
                        </span>
                    </div>
                @endif
                <div class="mt-2 flex justify-between text-lg font-semibold"><span>{{ __('admin.orders.total') }}</span><span>{{ number_format($order->total_amount, 0) }} ج.م</span></div>
            </div>
        </div>

        {{-- Delivery Tracking --}}
        <div class="ui-card-static p-6">
            <div class="mb-3 ui-section-title">Delivery Tracking</div>
            <div class="space-y-3">
                @if($order->status === 'new')
                    <div class="flex items-start gap-3 rounded-lg bg-sky-50 p-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-sky-900">Order Received</div>
                            <div class="text-xs text-sky-700">Waiting to be processed</div>
                        </div>
                    </div>
                @endif

                @if($order->status === 'processing')
                    <div class="flex items-start gap-3 rounded-lg bg-amber-50 p-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-amber-900">Preparing Order</div>
                            <div class="text-xs text-amber-700">Items being packed for shipment</div>
                        </div>
                    </div>
                @endif

                @if($order->status === 'shipped')
                    <div class="flex items-start gap-3 rounded-lg bg-indigo-50 p-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-indigo-900">In Transit</div>
                            <div class="text-xs text-indigo-700">Order is with delivery partner</div>
                        </div>
                    </div>
                @endif

                @if($order->status === 'delivered')
                    <div class="flex items-start gap-3 rounded-lg bg-emerald-50 p-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-emerald-900">Delivered</div>
                            <div class="text-xs text-emerald-700">Order successfully delivered</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Status Actions --}}
        <div class="ui-card-static p-6 space-y-2">
            <div class="mb-2 ui-section-title">{{ __('admin.orders.update_status') }}</div>
            @foreach([
                'processing' => __('admin.orders.mark_processing'),
                'shipped' => __('admin.orders.mark_shipped'),
                'delivered' => __('admin.orders.mark_delivered'),
            ] as $st => $label)
                @if($order->status !== $st && !in_array($order->status, ['cancelled','refunded','delivered'], true))
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $st }}">
                        <button class="ui-btn ui-btn-ghost ui-btn-block">{{ $label }}</button>
                    </form>
                @endif
            @endforeach

            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-2 ui-divider pt-3" onsubmit="return confirm(@json(__('admin.confirm')))">
                @csrf @method('PATCH')
                <select name="status" class="ui-select">
                    <option value="cancelled">{{ __('admin.orders.cancel') }}</option>
                    <option value="refunded">{{ __('admin.orders.refund') }}</option>
                </select>
                <textarea name="cancellation_reason" rows="2" placeholder="{{ __('admin.orders.cancel_reason') }}" class="ui-textarea">{{ $order->cancellation_reason }}</textarea>
                <button class="ui-btn ui-btn-block border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">{{ __('admin.confirm') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
