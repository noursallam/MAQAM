@extends('admin.layouts.app')
@section('title', __('admin.merchants.title'))
@section('actions')
<a href="{{ route('admin.merchants.inbox') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.merchant_inbox') }}</a>
<a href="{{ route('admin.merchants.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.merchants.add') }}</a>
@endsection
@section('content')
<form class="ui-toolbar">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.search') }}" class="ui-input max-w-md">
    <select name="status" class="ui-select max-w-xs" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        <option value="pending" @selected(request('status')==='pending')>{{ __('admin.pending') }}</option>
        <option value="approved" @selected(request('status')==='approved')>{{ __('admin.approved') }}</option>
    </select>
</form>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($merchants as $merchant)
        <div class="ui-card p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold">{{ $merchant->business_name }}</div>
                    <div class="ui-muted mt-1">{{ $merchant->user?->full_name }}</div>
                </div>
                <span class="ui-badge {{ $merchant->is_approved ? 'ui-badge-ok' : 'ui-badge-warn' }}">
                    {{ $merchant->is_approved ? __('admin.approved') : __('admin.pending') }}
                </span>
            </div>
            <div class="ui-card-soft mt-4 flex items-center justify-between px-3 py-2">
                <code class="text-xs" dir="ltr">{{ $merchant->merchant_code }}</code>
                <button type="button" onclick="copyText(@json($merchant->merchant_code))" class="text-[10px] font-semibold text-maqam-gold-dark">{{ __('admin.copy') }}</button>
            </div>
            <div class="mt-4 flex gap-2">
                @unless($merchant->is_approved)
                    <form method="POST" action="{{ route('admin.merchants.approve', $merchant) }}">@csrf<button class="ui-btn ui-btn-primary text-xs">{{ __('admin.merchants.approve') }}</button></form>
                @endunless
                <a href="{{ route('admin.merchants.edit', $merchant) }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.edit') }}</a>
            </div>
        </div>
    @empty
        <div class="ui-empty col-span-full">{{ __('admin.empty_title') }}</div>
    @endforelse
</div>
<div class="mt-6">{{ $merchants->links() }}</div>
@endsection
