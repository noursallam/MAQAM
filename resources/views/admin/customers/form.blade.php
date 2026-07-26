@extends('admin.layouts.app')
@section('title', $customer->exists ? __('admin.edit') : __('admin.customers.add'))
@section('content')
<form method="POST" action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" class="ui-card-static max-w-2xl space-y-4 p-6">
    @csrf
    @if($customer->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">الاسم الكامل</label>
            <input name="full_name" value="{{ old('full_name', $customer->user?->full_name) }}" required class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.merchants.phone') }}</label>
            <input name="phone_number" value="{{ old('phone_number', $customer->user?->phone_number) }}" required class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $customer->user?->email) }}" class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.password') }} {{ $customer->exists ? '(اختياري)' : '' }}</label>
            <input type="password" name="password" {{ $customer->exists ? '' : 'required' }} class="ui-input" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.nav.ranks') }}</label>
            <select name="rank_id" class="ui-select">
                @foreach($ranks as $rank)
                    <option value="{{ $rank->id }}" @selected(old('rank_id', $customer->rank_id)==$rank->id)>{{ $rank->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.customers.points_balance') }}</label>
            <input type="number" name="points_balance" value="{{ old('points_balance', $customer->points_balance ?? 0) }}" class="ui-input">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->user?->is_active ?? true))> {{ __('admin.active') }}</label>
    <button class="ui-btn ui-btn-primary">{{ __('admin.save') }}</button>
</form>
@endsection
