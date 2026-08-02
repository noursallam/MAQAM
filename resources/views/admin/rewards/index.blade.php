@extends('admin.layouts.app')

@section('title', __('admin.rewards.title'))
@section('subtitle', __('admin.rewards.subtitle'))

@section('content')
<div class="grid gap-3 sm:grid-cols-3 mb-5">
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.rewards.available') }}</div>
        <div class="ui-kpi-value">{{ $stats['available'] }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.rewards.used') }}</div>
        <div class="ui-kpi-value">{{ $stats['used'] }}</div>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.rewards.total') }}</div>
        <div class="ui-kpi-value">{{ $stats['total'] }}</div>
    </div>
</div>

<form method="GET" class="ui-panel mb-5 flex flex-wrap gap-3 items-end">
    <div class="min-w-[10rem]">
        <label class="mb-1 block text-xs text-maqam-muted">{{ __('admin.rewards.status') }}</label>
        <select name="status" class="ui-select">
            <option value="">{{ __('admin.all') }}</option>
            @foreach(['available','used','expired','revoked'] as $st)
                <option value="{{ $st }}" @selected(request('status')===$st)>{{ __('admin.rewards.status_'.$st) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[10rem]">
        <label class="mb-1 block text-xs text-maqam-muted">{{ __('admin.rewards.type') }}</label>
        <select name="type" class="ui-select">
            <option value="">{{ __('admin.all') }}</option>
            @foreach(['coupon','discount','product'] as $type)
                <option value="{{ $type }}" @selected(request('type')===$type)>{{ __('admin.rewards.type_'.$type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[10rem]">
        <label class="mb-1 block text-xs text-maqam-muted">{{ __('admin.rewards.source') }}</label>
        <select name="source" class="ui-select">
            <option value="">{{ __('admin.all') }}</option>
            @foreach(['wheel','admin','campaign'] as $src)
                <option value="{{ $src }}" @selected(request('source')===$src)>{{ __('admin.rewards.source_'.$src) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[12rem] flex-1">
        <label class="mb-1 block text-xs text-maqam-muted">{{ __('admin.search') }}</label>
        <input type="search" name="q" value="{{ request('q') }}" class="ui-input" placeholder="{{ __('admin.rewards.search_hint') }}" dir="auto">
    </div>
    <button class="ui-btn ui-btn-dark">{{ __('admin.filter') }}</button>
</form>

<div class="ui-table-wrap">
    <table class="ui-table">
        <thead>
        <tr>
            <th>{{ __('admin.orders.customer') }}</th>
            <th>{{ __('admin.rewards.type') }}</th>
            <th>{{ __('admin.rewards.code') }}</th>
            <th>{{ __('admin.rewards.detail') }}</th>
            <th>{{ __('admin.rewards.status') }}</th>
            <th>{{ __('admin.rewards.source') }}</th>
            <th>{{ __('admin.rewards.expires') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($rewards as $reward)
            <tr>
                <td>
                    <a href="{{ route('admin.customers.show', $reward->customer_id) }}" class="font-medium hover:text-maqam-gold-dark">
                        {{ $reward->customer?->user?->full_name ?? '—' }}
                    </a>
                </td>
                <td>{{ __('admin.rewards.type_'.$reward->type) }}</td>
                <td><code dir="ltr">{{ $reward->code ?? '—' }}</code></td>
                <td class="text-sm">
                    @if($reward->type === 'product')
                        {{ $reward->product?->name_ar ?: $reward->product?->name_en ?: ('#'.$reward->product_id) }}
                    @elseif($reward->amount_type === 'percentage')
                        {{ $reward->amount_value }}%
                        @if($reward->coupon)<span class="ui-muted">({{ $reward->coupon->code }})</span>@endif
                    @elseif($reward->amount_value !== null)
                        {{ number_format((float) $reward->amount_value, 2) }} ج.م
                        @if($reward->coupon)<span class="ui-muted">({{ $reward->coupon->code }})</span>@endif
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span class="ui-badge {{ $reward->status === 'available' ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                        {{ __('admin.rewards.status_'.$reward->status) }}
                    </span>
                </td>
                <td class="ui-muted">{{ __('admin.rewards.source_'.$reward->source) }}</td>
                <td class="ui-muted text-sm">{{ $reward->expires_at?->format('Y-m-d') ?? '—' }}</td>
                <td>
                    @if($reward->status === 'available')
                        <form method="POST" action="{{ route('admin.rewards.revoke', $reward) }}" onsubmit="return confirm(@json(__('admin.rewards.revoke_confirm')))">
                            @csrf
                            <button class="ui-btn ui-btn-ghost text-xs text-red-700">{{ __('admin.rewards.revoke') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-10 text-center ui-muted">{{ __('admin.empty_title') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4">{{ $rewards->links() }}</div>
</div>
@endsection
