@extends('admin.layouts.app')

@section('title', __('admin.risk.title'))
@section('subtitle', __('admin.risk.subtitle'))

@section('content')
@php
    $sev = [
        'low' => 'ui-badge-ok',
        'medium' => 'ui-badge-warn',
        'high' => 'ui-badge-danger',
    ];
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="ui-card-static p-6 xl:col-span-1">
        <h2 class="mb-4 ui-section-title">{{ __('admin.risk.flagged') }}</h2>
        @forelse($frozen as $row)
            <div class="mb-3 rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-medium">{{ $row['user']->full_name }}</div>
                        <div class="ui-muted" dir="ltr">{{ $row['user']->phone_number }}</div>
                        <div class="mt-1 ui-muted">{{ $row['reason'] }}</div>
                    </div>
                    <span class="ui-badge {{ $sev[$row['severity']] }}">{{ __('admin.risk.'.$row['severity']) }}</span>
                </div>
                <form method="POST" action="{{ route('admin.risk.unfreeze', $row['user']) }}" class="mt-3">
                    @csrf
                    <button class="ui-btn ui-btn-primary !bg-emerald-600 !border-emerald-600 !text-white text-xs !px-3 !py-1.5">{{ __('admin.risk.unfreeze') }}</button>
                </form>
            </div>
        @empty
            <p class="ui-muted">{{ __('admin.risk.empty') }}</p>
        @endforelse
    </div>

    <div class="ui-card-static p-6 xl:col-span-1">
        <h2 class="mb-4 ui-section-title">{{ __('admin.risk.geo_velocity') }}</h2>
        @forelse($geoAnomalies as $inc)
            <div class="mb-3 rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4 text-sm">
                <div class="flex justify-between">
                    <div class="font-medium">{{ $inc['customer']?->user?->full_name }}</div>
                    <span class="ui-badge {{ $sev[$inc['severity']] }}">{{ __('admin.risk.'.$inc['severity']) }}</span>
                </div>
                <div class="mt-2 ui-muted">
                    {{ $inc['distance_km'] }} كم خلال {{ $inc['minutes'] }} د · ≈ {{ $inc['speed'] }} كم/س
                </div>
                @if($inc['customer']?->user)
                    <form method="POST" action="{{ route('admin.risk.freeze', $inc['customer']->user) }}" class="mt-3 flex gap-2" onsubmit="return confirm(@json(__('admin.confirm')))">
                        @csrf
                        <input type="hidden" name="reason" value="Geo-velocity {{ $inc['speed'] }} km/h">
                        <button class="ui-btn ui-btn-dark !bg-red-600 !border-red-600 text-xs !px-3 !py-1.5">{{ __('admin.risk.freeze') }}</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="ui-muted">{{ __('admin.risk.empty') }}</p>
        @endforelse
    </div>

    <div class="ui-card-static p-6 xl:col-span-1">
        <h2 class="mb-4 ui-section-title">{{ __('admin.qr.sync_failed') }}</h2>
        @forelse($failedSync as $scan)
            <div class="mb-3 rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4 text-sm">
                <div class="font-medium">{{ $scan->customer?->user?->full_name }}</div>
                <div class="font-mono text-xs ui-muted" dir="ltr">{{ $scan->qrCode?->serial_code }}</div>
                <div class="mt-1 text-[10px] text-red-700">{{ $scan->scanned_at?->diffForHumans() }}</div>
            </div>
        @empty
            <p class="ui-muted">—</p>
        @endforelse
    </div>
</div>
@endsection
