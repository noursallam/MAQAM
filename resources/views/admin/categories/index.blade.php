@extends('admin.layouts.app')
@section('title', __('admin.nav.store_categories'))
@section('subtitle', 'أقسام المتجر — أضف القسم أولاً قبل المنتجات')
@section('actions')
<a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-primary">إضافة قسم</a>
<a href="{{ route('admin.products.create') }}" class="ui-btn ui-btn-ghost">إضافة منتج</a>
@endsection
@section('content')
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($categories as $category)
        <a href="{{ route('admin.categories.edit', $category) }}" class="group block">
            <article class="overflow-hidden rounded-xl border border-[#D8D4CB] bg-white transition-all hover:border-maqam-gold hover:shadow-md">
                <div class="relative aspect-square overflow-hidden bg-[#EAE7DF]">
                    @if($category->hasImage())
                        <img src="{{ $category->image_url }}" alt="{{ $category->name_ar }}" 
                             class="h-full w-full object-cover transition-transform group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <svg class="h-16 w-16 text-maqam-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                    @endif
                    <span class="absolute start-3 top-3 rounded-full px-2 py-1 text-xs font-medium {{ $category->is_active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                        {{ $category->is_active ? 'ظاهر' : 'مخفي' }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="truncate font-semibold text-maqam-ink">{{ $category->name_ar }}</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-maqam-muted">
                        <span>{{ $category->name_en }}</span>
                        <span class="text-[#D8D4CB]">·</span>
                        <span dir="ltr" class="font-mono">{{ $category->slug }}</span>
                    </div>
                    @if($category->parent)
                        <div class="mt-2 text-xs text-maqam-muted">تابع لـ: {{ $category->parent->name_ar }}</div>
                    @endif
                    <div class="mt-3 text-sm font-medium text-maqam-gold-dark">
                        {{ $category->products()->count() }} منتج
                    </div>
                </div>
            </article>
        </a>
    @empty
        <div class="ui-empty col-span-full">
            <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-[#EAE7DF]">
                <svg class="h-10 w-10 text-maqam-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <p class="font-semibold">لا توجد أقسام بعد</p>
            <p class="ui-muted mt-2">تنظّم الأقسام منتجات المتجر</p>
            <a href="{{ route('admin.categories.create') }}" class="ui-btn ui-btn-primary mt-6">إضافة أول قسم</a>
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $categories->links() }}</div>
@endsection
