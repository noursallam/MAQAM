@extends('admin.layouts.app')

@section('title', __('admin.nav.products'))
@section('subtitle', __('admin.commerce.catalog_subtitle'))

@section('actions')
<a href="{{ route('admin.products.barcodes.export') }}" class="ui-btn ui-btn-ghost">{{ __('admin.commerce.export_barcodes') }}</a>
<a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-ghost">+ {{ __('admin.commerce.add_category') }}</a>
<a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.commerce.add_product') }}</a>
@endsection

@section('content')
<form class="ui-toolbar">
    <input name="q" value="{{ request('q') }}" placeholder="ابحث باسم المنتج أو الكود…" class="ui-input max-w-md">
    <label class="ui-chip">
        <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock')) onchange="this.form.submit()">
        مخزون منخفض
    </label>
</form>

<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @forelse($products as $product)
        @php
            $qty = $product->stock_quantity;
            $badgeClass = $qty <= 0 ? 'bg-red-100 text-red-700 border-red-200' : ($qty < 50 ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200');
            $badgeLabel = $qty <= 0 ? __('admin.commerce.out_of_stock') : ($qty < 50 ? __('admin.commerce.low_stock') : __('admin.commerce.high_stock'));
            $thumb = $product->thumbnailUrl();
        @endphp
        <a href="{{ route('admin.products.edit', $product) }}" class="group block">
            <article class="overflow-hidden rounded-xl border border-[#D8D4CB] bg-white transition-all hover:border-maqam-gold hover:shadow-md">
                <div class="relative aspect-square overflow-hidden bg-[#EAE7DF]">
                    @if($thumb)
                        <img src="{{ $thumb }}" alt="{{ $product->name_ar ?: $product->name_en }}" 
                             class="h-full w-full object-cover transition-transform group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <svg class="h-16 w-16 text-maqam-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    @endif
                    <span class="absolute start-3 top-3 rounded-full px-2 py-1 text-xs font-medium {{ $badgeClass }}">
                        {{ $badgeLabel }}
                    </span>
                    @if($qty > 0 && $qty < 50)
                        <div class="absolute end-3 top-3 flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="truncate font-semibold text-maqam-ink">{{ $product->name_ar ?: $product->name_en }}</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-maqam-muted">
                        <span>{{ $product->category?->name_ar ?? '—' }}</span>
                        <span class="text-[#D8D4CB]">·</span>
                        <span dir="ltr" class="font-mono">{{ $product->sku }}</span>
                    </div>
                    <div class="mt-3 flex items-end justify-between">
                        <div class="text-lg font-bold text-maqam-ink">{{ number_format($product->price, 0) }} <span class="text-sm font-medium text-maqam-muted">ج.م</span></div>
                        <div class="text-sm font-medium {{ $qty <= 0 ? 'text-red-600' : ($qty < 50 ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ $qty }} في المخزون
                        </div>
                    </div>
                </div>
            </article>
        </a>
    @empty
        <div class="ui-empty col-span-full">
            <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-[#EAE7DF]">
                <svg class="h-10 w-10 text-maqam-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <p class="font-semibold">لا توجد منتجات بعد</p>
            <p class="ui-muted mt-2">ابدأ بإضافة الأقسام، ثم أضف المنتجات مع صورها.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-ghost">إضافة قسم</a>
                <a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-primary">إضافة منتج</a>
            </div>
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
