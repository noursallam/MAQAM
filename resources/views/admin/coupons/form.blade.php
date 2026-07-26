@extends('admin.layouts.app')
@section('title', $coupon->exists ? __('admin.edit') : __('admin.coupons.add'))
@section('content')
<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="ui-card-static max-w-xl space-y-4 p-6">
    @csrf @if($coupon->exists) @method('PUT') @endif
    <div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.code') }}</label>
        <input name="code" value="{{ old('code', $coupon->code) }}" required class="ui-input" dir="ltr">
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.type') }}</label>
            <select name="type" class="ui-select">
                <option value="percentage" @selected(old('type', $coupon->type)==='percentage')>نسبة مئوية</option>
                <option value="fixed" @selected(old('type', $coupon->type)==='fixed')>قيمة ثابتة</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.value') }}</label>
            <input type="number" step="0.01" name="value" value="{{ old('value', $coupon->value) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">النطاق</label>
            <select name="scope" class="ui-select">
                @foreach(['all'=>'الكل','category'=>'فئة','product'=>'منتج','merchant'=>'تاجر'] as $scope => $label)
                    <option value="{{ $scope }}" @selected(old('scope', $coupon->scope)===$scope)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">حد الاستخدام</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">من</label>
            <input type="datetime-local" name="valid_from" value="{{ old('valid_from', optional($coupon->valid_from)->format('Y-m-d\TH:i')) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">إلى</label>
            <input type="datetime-local" name="valid_to" value="{{ old('valid_to', optional($coupon->valid_to)->format('Y-m-d\TH:i')) }}" required class="ui-input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">حد أدنى للطلب</label>
            <input type="number" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}" class="ui-input">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))> {{ __('admin.active') }}</label>
    <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
</form>
@endsection
