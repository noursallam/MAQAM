@extends('admin.layouts.app')
@section('title', $coupon->exists ? __('admin.edit') : __('admin.coupons.add'))
@section('content')
<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="ui-card-static max-w-2xl space-y-4 p-6">
    @csrf @if($coupon->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.code') }}</label>
            <input name="code" value="{{ old('code', $coupon->code) }}" required class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.name') }}</label>
            <input name="name" value="{{ old('name', $coupon->name) }}" class="ui-input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.description') }}</label>
            <textarea name="description" rows="2" class="ui-input">{{ old('description', $coupon->description) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.type') }}</label>
            <select name="type" class="ui-select">
                <option value="percentage" @selected(old('type', $coupon->type)==='percentage')>{{ __('admin.coupons.type_percentage') }}</option>
                <option value="fixed" @selected(old('type', $coupon->type)==='fixed')>{{ __('admin.coupons.type_fixed') }}</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.value') }}</label>
            <input type="number" step="0.01" name="value" value="{{ old('value', $coupon->value) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.scope') }}</label>
            <select name="scope" class="ui-select">
                @foreach(['all','category','product','merchant'] as $scope)
                    <option value="{{ $scope }}" @selected(old('scope', $coupon->scope)===$scope)>{{ __('admin.coupons.scope_'.$scope) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.assignment') }}</label>
            <select name="assignment" class="ui-select">
                <option value="public_code" @selected(old('assignment', $coupon->assignment ?? 'public_code')==='public_code')>{{ __('admin.coupons.assignment_public_code') }}</option>
                <option value="personal_grant" @selected(old('assignment', $coupon->assignment)==='personal_grant')>{{ __('admin.coupons.assignment_personal_grant') }}</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.usage_limit') }}</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.usage_limit_per_customer') }}</label>
            <input type="number" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $coupon->usage_limit_per_customer) }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.valid_from') }}</label>
            <input type="datetime-local" name="valid_from" value="{{ old('valid_from', optional($coupon->valid_from)->format('Y-m-d\TH:i')) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.valid_to') }}</label>
            <input type="datetime-local" name="valid_to" value="{{ old('valid_to', optional($coupon->valid_to)->format('Y-m-d\TH:i')) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.min_order') }}</label>
            <input type="number" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.max_discount') }}</label>
            <input type="number" step="0.01" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}" class="ui-input">
        </div>
    </div>
    <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))> {{ __('admin.active') }}</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $coupon->is_public ?? true))> {{ __('admin.coupons.is_public') }}</label>
    </div>
    <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
</form>
@endsection
