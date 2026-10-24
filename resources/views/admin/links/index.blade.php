@extends('admin.layouts.app')
@section('title', __('admin.links.title'))
@section('subtitle', __('admin.links.subtitle'))
@section('actions')
<button type="button" class="ui-btn ui-btn-ghost" onclick="copyText(@js(route('store.links')))">{{ __('admin.links.copy') }}</button>
<a href="{{ route('store.links') }}" target="_blank" rel="noopener" class="ui-btn ui-btn-primary">{{ __('admin.links.open') }}</a>
@endsection
@section('content')
@php $platforms = \App\Models\LinkPageItem::PLATFORMS; @endphp
<div class="grid gap-6 lg:grid-cols-[340px_1fr]">
    <div class="space-y-6">
        <form method="POST" action="{{ route('admin.links.store') }}" class="ui-card-static space-y-3 p-5">
            @csrf
            <h2 class="ui-section-title">{{ __('admin.links.add') }}</h2>
            <select name="platform" class="ui-select">
                @foreach($platforms as $platform)
                    <option value="{{ $platform }}">{{ __('admin.links.p_'.$platform) }}</option>
                @endforeach
            </select>
            <input name="url" required class="ui-input" dir="ltr" placeholder="https://">
            <input name="label_ar" class="ui-input" placeholder="{{ __('admin.links.label_ar') }}">
            <input name="label_en" class="ui-input" dir="ltr" placeholder="{{ __('admin.links.label_en') }}">
            <p class="ui-muted">{{ __('admin.links.label_hint') }}</p>
            <button class="ui-btn ui-btn-primary w-full">{{ __('admin.links.add') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.links.bio') }}" class="ui-card-static space-y-3 p-5">
            @csrf
            <h2 class="ui-section-title">{{ __('admin.links.bio') }}</h2>
            <textarea name="bio_ar" rows="2" maxlength="300" class="ui-textarea" placeholder="{{ __('store.links.default_bio') }}">{{ $bioAr }}</textarea>
            <textarea name="bio_en" rows="2" maxlength="300" class="ui-textarea" dir="ltr" placeholder="English">{{ $bioEn }}</textarea>
            <button class="ui-btn ui-btn-ghost w-full">{{ __('admin.links.save') }}</button>
        </form>
    </div>

    <div class="space-y-3">
        @foreach($items as $item)
            <form method="POST" action="{{ route('admin.links.update', $item) }}" class="ui-card-static p-4">
                @csrf
                @method('PUT')
                <div class="grid gap-3 md:grid-cols-[150px_1fr_90px]">
                    <select name="platform" class="ui-select">
                        @foreach($platforms as $platform)
                            <option value="{{ $platform }}" @selected($item->platform === $platform)>{{ __('admin.links.p_'.$platform) }}</option>
                        @endforeach
                    </select>
                    <input name="url" value="{{ $item->url }}" required class="ui-input" dir="ltr">
                    <input type="number" name="sort_order" value="{{ $item->sort_order }}" min="0" class="ui-input" dir="ltr" title="{{ __('admin.links.order') }}">
                </div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <input name="label_ar" value="{{ $item->label_ar }}" class="ui-input" placeholder="{{ __('admin.links.label_ar') }}">
                    <input name="label_en" value="{{ $item->label_en }}" class="ui-input" dir="ltr" placeholder="{{ __('admin.links.label_en') }}">
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="h-4 w-4 accent-[#C5A059]">
                        {{ __('admin.links.visible') }}
                    </label>
                    <span class="ui-muted">{{ __('admin.links.clicks', ['count' => number_format($item->clicks)]) }}</span>
                    <div class="flex gap-2">
                        <button class="ui-btn ui-btn-primary !py-1.5 text-xs">{{ __('admin.links.save') }}</button>
                        <button form="delete-{{ $item->id }}" class="ui-btn ui-btn-ghost !py-1.5 text-xs text-red-700">{{ __('admin.links.delete') }}</button>
                    </div>
                </div>
            </form>
            <form id="delete-{{ $item->id }}" method="POST" action="{{ route('admin.links.destroy', $item) }}" onsubmit="return confirm(@js(__('admin.links.delete_confirm')))">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>
</div>
@endsection
