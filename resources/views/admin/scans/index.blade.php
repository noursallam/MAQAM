@extends('admin.layouts.app')

@section('title', __('admin.qr.scan_monitor'))
@section('subtitle', __('admin.qr.scan_subtitle'))

@section('content')
<form class="ui-toolbar">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.qr.serial') }} / {{ __('admin.orders.customer') }}" class="ui-input max-w-xs">
    <select name="sync_status" class="ui-select max-w-[12rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.qr.sync_status') }}</option>
        <option value="synced" @selected(request('sync_status')==='synced')>{{ __('admin.qr.synced') }}</option>
        <option value="pending" @selected(request('sync_status')==='pending')>{{ __('admin.qr.sync_pending') }}</option>
        <option value="failed" @selected(request('sync_status')==='failed')>{{ __('admin.qr.sync_failed') }}</option>
    </select>
    <select name="is_offline" class="ui-select max-w-[10rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        <option value="0" @selected(request('is_offline')==='0')>{{ __('admin.qr.online') }}</option>
        <option value="1" @selected(request('is_offline')==='1')>{{ __('admin.qr.offline') }}</option>
    </select>
    <select name="merchant_id" class="ui-select max-w-xs" onchange="this.form.submit()">
        <option value="">{{ __('admin.nav.merchants') }}</option>
        @foreach($merchants as $m)
            <option value="{{ $m->id }}" @selected(request('merchant_id')==$m->id)>{{ $m->business_name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="ui-input max-w-[10rem]">
    <input type="date" name="to" value="{{ request('to') }}" class="ui-input max-w-[10rem]">
    <button class="ui-btn ui-btn-dark">{{ __('admin.filter') }}</button>
</form>

<div class="space-y-3">
    @forelse($scans as $scan)
        <div class="ui-card-static p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="font-semibold">{{ $scan->customer?->user?->full_name ?? '—' }}</div>
                    <div class="mt-1 ui-muted">{{ $scan->merchant?->business_name ?? __('admin.qr.no_merchant') }}</div>
                    <div class="mt-2 font-mono text-xs" dir="ltr">{{ $scan->qrCode?->serial_code }}</div>
                </div>
                <div class="text-end">
                    <div class="font-semibold text-maqam-gold-dark">+{{ $scan->points_awarded_customer }} {{ __('admin.qr.pts_customer') }}</div>
                    <div class="ui-muted">+{{ $scan->points_awarded_merchant }} {{ __('admin.qr.pts_merchant') }}</div>
                    <div class="mt-2 flex flex-wrap justify-end gap-1">
                        <span class="ui-badge ui-badge-muted">{{ $scan->is_offline ? __('admin.qr.offline') : __('admin.qr.online') }}</span>
                        <span class="ui-badge {{ $scan->sync_status==='failed'?'ui-badge-danger':($scan->sync_status==='pending'?'ui-badge-warn':'ui-badge-ok') }}">
                            {{ __('admin.qr.'.($scan->sync_status === 'pending' ? 'sync_pending' : ($scan->sync_status === 'failed' ? 'sync_failed' : 'synced'))) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-[11px] ui-muted">
                <span>{{ $scan->scanned_at?->format('Y-m-d H:i') }}</span>
                @if($scan->scan_location_lat)
                    <span>{{ __('admin.qr.geo') }}: <span dir="ltr">{{ $scan->scan_location_lat }}, {{ $scan->scan_location_lng }}</span></span>
                @endif
                @if($scan->qrCode?->categoryPrize)
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $scan->qrCode->categoryPrize->background_color }}"></span>
                        {{ $scan->qrCode->categoryPrize->name_ar }}
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="ui-empty">{{ __('admin.qr.empty_scans') }}</div>
    @endforelse
</div>
<div class="mt-6">{{ $scans->links() }}</div>
@endsection
