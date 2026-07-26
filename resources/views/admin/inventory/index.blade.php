@extends('admin.layouts.app')

@section('title', __('admin.commerce.inventory'))
@section('subtitle', __('admin.commerce.inventory_subtitle'))

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.commerce.low_stock') }} (&lt; {{ $threshold }})</div>
        <div class="ui-kpi-value text-amber-700">{{ $low }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.commerce.out_of_stock') }}</div>
        <div class="ui-kpi-value text-red-700">{{ $out }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.nav.catalog') }}</div>
        <div class="ui-kpi-value">{{ $products->total() }}</div>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($products as $product)
        @php
            $qty = $product->stock_quantity;
            $badgeClass = $qty <= 0 ? 'ui-badge-danger' : ($qty < $threshold ? 'ui-badge-warn' : ($qty < 100 ? 'ui-badge-gold' : 'ui-badge-ok'));
            $badgeLabel = $qty <= 0 ? __('admin.commerce.out_of_stock') : ($qty < $threshold ? __('admin.commerce.low_stock') : ($qty < 100 ? __('admin.commerce.medium_stock') : __('admin.commerce.high_stock')));
        @endphp
        <div class="ui-card p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold">{{ $product->name_ar }}</div>
                    <div class="ui-muted mt-1">{{ $product->name_en }} · {{ $product->sku }}</div>
                </div>
                <span class="ui-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
            <div class="ui-divider mt-4 flex justify-between pt-3 text-sm">
                <span>{{ __('admin.commerce.stock') }}: <strong>{{ $qty }}</strong></span>
                <span>{{ number_format($product->price, 0) }} ج.م</span>
            </div>
            <form method="POST" action="{{ route('admin.inventory.adjust', $product) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="number" name="stock_quantity" min="0" value="{{ $qty }}" class="ui-input">
                <button class="ui-btn ui-btn-dark shrink-0 text-xs">{{ __('admin.commerce.adjust_stock') }}</button>
            </form>
        </div>
    @endforeach
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
