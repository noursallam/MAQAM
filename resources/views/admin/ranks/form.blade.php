@extends('admin.layouts.app')
@section('title', $rank->exists ? __('admin.edit') : __('admin.ranks.add'))
@section('content')
<form method="POST" action="{{ $rank->exists ? route('admin.ranks.update', $rank) : route('admin.ranks.store') }}" class="ui-card-static max-w-2xl space-y-4 p-6">
    @csrf @if($rank->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_ar') }}</label>
            <input name="name_ar" value="{{ old('name_ar', $rank->name_ar) }}" required class="ui-input" dir="rtl">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_en') }}</label>
            <input name="name_en" value="{{ old('name_en', $rank->name_en) }}" required class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.ranks.min_points') }}</label>
            <input type="number" name="min_points" value="{{ old('min_points', $rank->min_points ?? 0) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.ranks.max_points') }}</label>
            <input type="number" name="max_points" value="{{ old('max_points', $rank->max_points) }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">نقاط العميل / مسح</label>
            <input type="number" name="customer_points_per_scan" value="{{ old('customer_points_per_scan', $rank->customer_points_per_scan ?? 10) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.ranks.merchant_pts') }}</label>
            <input type="number" name="merchant_points_per_scan" value="{{ old('merchant_points_per_scan', $rank->merchant_points_per_scan ?? 5) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.ranks.wheel_prob') }} (0–1)</label>
            <input type="number" step="0.01" name="wheel_win_probability" value="{{ old('wheel_win_probability', $rank->wheel_win_probability ?? 0.2) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.ranks.wheel_cost') }}</label>
            <input type="number" name="wheel_cost_points" value="{{ old('wheel_cost_points', $rank->wheel_cost_points ?? 50) }}" required class="ui-input">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rank->is_active ?? true))> {{ __('admin.active') }}</label>
    <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
</form>
@endsection
