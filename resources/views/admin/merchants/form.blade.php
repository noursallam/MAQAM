@extends('admin.layouts.app')
@section('title', $merchant->exists ? __('admin.edit') : __('admin.merchants.add'))
@section('content')
<form method="POST" action="{{ $merchant->exists ? route('admin.merchants.update', $merchant) : route('admin.merchants.store') }}" class="ui-card-static max-w-2xl space-y-4 p-6">
    @csrf
    @if($merchant->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.owner') }}</label>
            <input name="full_name" value="{{ old('full_name', $merchant->user?->full_name) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.phone') }}</label>
            <input name="phone_number" value="{{ old('phone_number', $merchant->user?->phone_number) }}" required class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $merchant->user?->email) }}" class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.password') }} {{ $merchant->exists ? '(اختياري)' : '' }}</label>
            <input type="password" name="password" {{ $merchant->exists ? '' : 'required' }} class="ui-input" dir="ltr">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.business_name') }}</label>
            <input name="business_name" value="{{ old('business_name', $merchant->business_name) }}" required class="ui-input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.address') }}</label>
            <textarea name="business_address" class="ui-textarea" rows="2">{{ old('business_address', $merchant->business_address) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.merchant_code') }}</label>
            <input name="merchant_code" value="{{ old('merchant_code', $merchant->merchant_code) }}" placeholder="يُولَّد تلقائياً إن تُرك فارغاً" class="ui-input" dir="ltr">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_approved" value="1" @checked(old('is_approved', $merchant->is_approved))> {{ __('admin.approved') }}</label>
    <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
</form>
@endsection
