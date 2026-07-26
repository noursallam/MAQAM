@extends('admin.layouts.app')

@section('title', __('admin.customers.title'))
@section('subtitle', __('admin.customers.subtitle'))

@section('actions')
<a href="{{ route('admin.customers.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.customers.add') }}</a>
@endsection

@section('content')
<form class="ui-toolbar">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.search') }}" class="ui-input max-w-md">
    <label class="ui-chip">
        <input type="checkbox" name="frozen" value="1" @checked(request('frozen')==='1') onchange="this.form.submit()">
        {{ __('admin.customers.frozen') }}
    </label>
</form>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($customers as $customer)
        <a href="{{ route('admin.customers.show', $customer) }}" class="ui-card block p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="font-semibold">{{ $customer->user?->full_name }}</div>
                    <div class="ui-muted mt-1" dir="ltr">{{ $customer->user?->phone_number }}</div>
                </div>
                <span class="ui-badge {{ $customer->user?->is_active ? 'ui-badge-ok' : 'ui-badge-danger' }}">
                    {{ $customer->user?->is_active ? __('admin.active') : __('admin.customers.frozen') }}
                </span>
            </div>
            <div class="ui-divider mt-4 flex items-center justify-between pt-3 text-sm">
                <span class="ui-badge ui-badge-gold">{{ $customer->rank?->name_ar ?? '—' }}</span>
                <span class="font-bold text-maqam-gold-dark">{{ number_format($customer->points_balance) }} {{ __('admin.customers.points') }}</span>
            </div>
        </a>
    @empty
        <div class="ui-empty col-span-full">
            <p class="font-semibold">{{ __('admin.empty_title') }}</p>
            <a href="{{ route('admin.customers.create') }}" class="ui-btn ui-btn-primary mt-4">{{ __('admin.customers.add') }}</a>
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $customers->links() }}</div>
@endsection
