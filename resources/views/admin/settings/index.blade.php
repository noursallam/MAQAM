@extends('admin.layouts.app')
@section('title', __('admin.settings.title'))
@section('subtitle', __('admin.settings.subtitle'))
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    @forelse($settings as $group => $items)
        <div class="ui-card-static p-6">
            <h2 class="ui-section-title mb-4">{{ match($group) {
                'general' => __('admin.settings.general'),
                'loyalty' => __('admin.settings.loyalty'),
                'wheel' => __('admin.settings.wheel'),
                'points' => __('admin.settings.points'),
                'security' => __('admin.settings.security'),
                'inventory' => __('admin.settings.inventory'),
                default => $group,
            } }}</h2>
            <div class="space-y-4">
                @foreach($items as $setting)
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ $setting->key }}</label>
                        @if($setting->description)
                            <p class="ui-muted mb-1">{{ $setting->description }}</p>
                        @endif
                        <input name="settings[{{ $setting->key }}]" value="{{ old('settings.'.$setting->key, $setting->value) }}" class="ui-input">
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="ui-empty">
            <p class="ui-muted">{{ __('admin.empty_title') }}</p>
        </div>
    @endforelse
    @if($settings->isNotEmpty())
        <button class="ui-btn ui-btn-primary">{{ __('admin.settings.save') }}</button>
    @endif
</form>
@endsection
