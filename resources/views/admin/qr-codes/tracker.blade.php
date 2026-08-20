@extends('admin.layouts.app')

@section('title', __('admin.qr.tracker_title'))
@section('subtitle', __('admin.qr.tracker_subtitle'))

@section('actions')
<a href="{{ route('admin.qr-codes.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.batches') }}</a>
@endsection

@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
    @foreach([
        'total' => __('admin.all'),
        'generated' => __('admin.qr.life_generated'),
        'printed' => __('admin.qr.life_printed'),
        'sold' => __('admin.qr.life_sold'),
        'scanned' => __('admin.qr.life_scanned'),
        'expired' => __('admin.qr.life_expired'),
    ] as $key => $label)
        <a href="{{ route('admin.qr-codes.tracker', $key === 'total' ? [] : ['lifecycle' => $key]) }}"
           class="rounded-xl border px-3 py-3 text-center transition {{ request('lifecycle', 'total') === $key || ($key === 'total' && !request('lifecycle')) ? 'border-maqam-gold bg-[#F8F4EC]' : 'border-[#D8D4CB] bg-white hover:border-maqam-gold' }}">
            <div class="text-lg font-bold text-maqam-ink">{{ $summary[$key] ?? 0 }}</div>
            <div class="ui-muted text-xs">{{ $label }}</div>
        </a>
    @endforeach
</div>

<form class="ui-toolbar mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.qr.search_serial') }}" class="ui-input max-w-xs" dir="ltr">
    <input name="batch_id" value="{{ request('batch_id') }}" placeholder="{{ __('admin.qr.batch_id') }}" class="ui-input max-w-xs" dir="ltr">
    <select name="lifecycle" class="ui-select max-w-[12rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        <option value="generated" @selected(request('lifecycle')==='generated')>{{ __('admin.qr.life_generated') }}</option>
        <option value="printed" @selected(request('lifecycle')==='printed')>{{ __('admin.qr.life_printed') }}</option>
        <option value="sold" @selected(request('lifecycle')==='sold')>{{ __('admin.qr.life_sold') }}</option>
        <option value="scanned" @selected(request('lifecycle')==='scanned')>{{ __('admin.qr.life_scanned') }}</option>
        <option value="expired" @selected(request('lifecycle')==='expired')>{{ __('admin.qr.life_expired') }}</option>
    </select>
    <button class="ui-btn ui-btn-dark">{{ __('admin.filter') }}</button>
</form>

<div class="mb-4 flex flex-wrap gap-2">
    <form method="POST" action="{{ route('admin.qr-codes.mark-printed') }}" class="flex flex-wrap items-center gap-2">
        @csrf
        <input type="hidden" name="batch_id" value="{{ request('batch_id') }}">
        <button class="ui-btn ui-btn-ghost" @if(!request('batch_id')) disabled @endif>{{ __('admin.qr.mark_batch_printed') }}</button>
    </form>
    <form method="POST" action="{{ route('admin.qr-codes.mark-sold') }}" class="flex flex-wrap items-center gap-2">
        @csrf
        <input type="hidden" name="batch_id" value="{{ request('batch_id') }}">
        <button class="ui-btn ui-btn-ghost" @if(!request('batch_id')) disabled @endif>{{ __('admin.qr.mark_batch_sold') }}</button>
    </form>
</div>

<div class="ui-card-static overflow-x-auto p-0">
    <table class="ui-table">
        <thead>
            <tr>
                <th>{{ __('admin.qr.serial') }}</th>
                <th>{{ __('admin.qr.batch_id') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.qr.generated_at') }}</th>
                <th>{{ __('admin.qr.printed_at') }}</th>
                <th>{{ __('admin.qr.sold_at') }}</th>
                <th>{{ __('admin.qr.scanned_at') }}</th>
                <th>{{ __('admin.qr.scanned_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($codes as $code)
                @php
                    $life = $code->lifecycleStatus();
                    $badge = match($life) {
                        'generated' => 'ui-badge-muted',
                        'printed' => 'ui-badge-warn',
                        'sold' => 'ui-badge-ok',
                        'scanned' => 'bg-blue-100 text-blue-800',
                        default => 'ui-badge-muted',
                    };
                    $lastScan = $code->scans->first();
                @endphp
                <tr>
                    <td class="font-mono text-xs" dir="ltr">{{ $code->serial_code }}</td>
                    <td class="font-mono text-xs" dir="ltr">{{ $code->batch_id ?: '—' }}</td>
                    <td><span class="ui-badge {{ $badge }}">{{ $code->lifecycleLabel() }}</span></td>
                    <td class="text-xs">{{ $code->generated_at?->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="text-xs">{{ $code->printed_at?->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="text-xs">{{ $code->sold_at?->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="text-xs">{{ $code->used_at?->format('Y-m-d H:i') ?: ($lastScan?->scanned_at?->format('Y-m-d H:i') ?: '—') }}</td>
                    <td class="text-xs">{{ $code->usedByCustomer?->user?->full_name ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-8 text-center text-maqam-muted">{{ __('admin.empty_title') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $codes->links() }}</div>
@endsection
