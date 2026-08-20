@extends('admin.layouts.app')

@section('title', __('admin.banners.title'))
@section('subtitle', __('admin.banners.subtitle'))

@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<div class="grid gap-5 lg:grid-cols-[340px_1fr]">
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="ui-card-static h-fit space-y-3 p-5">
        @csrf
        <h2 class="ui-section-title">{{ __('admin.banners.add') }}</h2>
        <p class="ui-muted text-sm">{{ __('admin.banners.add_hint') }}</p>
        <div>
            <label class="mb-1 block text-sm">{{ __('admin.banners.image') }} *</label>
            <input type="file" name="image" accept="image/*" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm">{{ __('admin.banners.title_ar') }}</label>
            <input name="title_ar" value="{{ old('title_ar') }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm">{{ __('admin.banners.title_en') }}</label>
            <input name="title_en" value="{{ old('title_en') }}" class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm">{{ __('admin.banners.link_url') }}</label>
            <input name="link_url" value="{{ old('link_url') }}" class="ui-input" dir="ltr" placeholder="https://">
        </div>
        <div>
            <label class="mb-1 block text-sm">{{ __('admin.banners.sort_order') }}</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="ui-input" dir="ltr">
        </div>
        <button class="ui-btn ui-btn-primary w-full">{{ __('admin.banners.add') }}</button>
    </form>

    <div>
        @if($banners->isEmpty())
            <div class="ui-empty">
                <p class="font-semibold">{{ __('admin.banners.empty') }}</p>
                <p class="ui-muted mt-2">{{ __('admin.banners.add_hint') }}</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($banners as $banner)
                    <article class="ui-card-static overflow-hidden p-0">
                        <div class="aspect-[16/9] bg-[#EAE7DF]">
                            @if($banner->imageUrl())
                                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title_ar ?: $banner->title_en ?: 'banner' }}" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $banner->title_ar ?: $banner->title_en ?: __('admin.banners.untitled') }}</div>
                                    <div class="ui-muted mt-0.5 text-xs">#{{ $banner->sort_order }} · {{ $banner->created_at?->diffForHumans() }}</div>
                                </div>
                                <span class="ui-badge {{ $banner->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                                    {{ $banner->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                                    @csrf
                                    <button class="ui-btn ui-btn-ghost text-xs">{{ $banner->is_active ? __('admin.banners.disable') : __('admin.banners.enable') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('{{ __('admin.banners.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ui-btn ui-btn-ghost text-xs text-red-700">{{ __('admin.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
