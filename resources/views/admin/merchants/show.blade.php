@extends('admin.layouts.app')

@section('title', __('admin.merchants.profile').' — '.$merchant->business_name)
@section('breadcrumbs')
<a href="{{ route('admin.merchants.index') }}" class="hover:text-maqam-ink">{{ __('admin.merchants.title') }}</a>
<span class="mx-1">/</span>
<span>{{ $merchant->business_name }}</span>
@endsection

@section('actions')
@unless($merchant->is_approved)
    <form method="POST" action="{{ route('admin.merchants.approve', $merchant) }}">@csrf<button class="ui-btn ui-btn-primary">{{ __('admin.merchants.approve') }}</button></form>
@endunless
<a href="{{ route('admin.merchants.edit', $merchant) }}" class="ui-btn ui-btn-ghost">{{ __('admin.edit') }}</a>
@endsection

@section('content')
@php
    $maxMonth = max(1, $months->max('count'));
    $customer = $merchant->user?->customer;
@endphp
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-1">
        <div class="ui-card-static overflow-hidden">
            <div class="bg-maqam-navy p-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/10">
                        @if($merchant->logo_url)
                            <img src="{{ app(\App\Services\MediaService::class)->url($merchant->logo_url) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="text-2xl font-bold text-maqam-gold">{{ mb_substr($merchant->business_name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-semibold">{{ $merchant->business_name }}</h2>
                        <span class="ui-badge mt-1 {{ $merchant->is_approved ? 'ui-badge-ok' : 'ui-badge-warn' }}">
                            {{ $merchant->is_approved ? __('admin.approved') : __('admin.pending') }}
                        </span>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between rounded-lg bg-white/10 px-3 py-2">
                    <code class="text-lg font-bold tracking-wider text-maqam-gold" dir="ltr">{{ $merchant->merchant_code }}</code>
                    <button type="button" onclick="copyText(@json($merchant->merchant_code))" class="text-xs font-semibold text-maqam-gold">{{ __('admin.copy') }}</button>
                </div>
            </div>
            <dl class="space-y-3 p-6 text-sm">
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.owner') }}</dt><dd class="font-medium">{{ $merchant->user?->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.phone') }}</dt><dd dir="ltr">{{ $merchant->user?->phone_number ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.email') }}</dt><dd dir="ltr" class="truncate">{{ $merchant->user?->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.address') }}</dt><dd class="text-end">{{ $merchant->business_address ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.joined') }}</dt><dd>{{ $merchant->created_at?->format('Y-m-d') }}</dd></div>
                @if($merchant->is_approved)
                    <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.approved_at') }}</dt><dd>{{ $merchant->approved_at?->format('Y-m-d') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.merchants.approved_by') }}</dt><dd>{{ $merchant->approvedBy?->user?->full_name ?? '—' }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="ui-muted">{{ __('admin.status') }}</dt>
                    <dd><span class="ui-badge {{ $merchant->user?->is_active ? 'ui-badge-ok' : 'ui-badge-danger' }}">{{ $merchant->user?->is_active ? __('admin.active') : __('admin.customers.frozen') }}</span></dd>
                </div>
            </dl>
            @if($customer)
                <div class="ui-divider px-6 pb-6 pt-4">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="text-xs font-semibold text-maqam-gold-dark underline">{{ __('admin.merchants.customer_account') }}</a>
                    <span class="ui-muted text-xs"> · {{ $customer->rank?->name_ar ?? '—' }} · {{ number_format($customer->points_balance) }} {{ __('admin.merchants.points') }}</span>
                </div>
            @endif
        </div>

        <div class="ui-card-static p-6">
            <h3 class="ui-section-title mb-4">{{ __('admin.merchants.monthly_scans') }}</h3>
            <div class="flex h-36 items-end gap-2">
                @foreach($months as $month)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-xs font-semibold">{{ $month['count'] }}</span>
                        <div class="w-full rounded-t bg-maqam-gold" style="height: {{ max(2, $month['count'] / $maxMonth * 100) }}px"></div>
                        <span class="ui-muted text-[10px]">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-4 xl:col-span-2">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
            @foreach([
                'total_scans' => $stats['total_scans'],
                'total_points' => $stats['total_points'],
                'unique_customers' => $stats['unique_customers'],
                'scans_this_month' => $stats['scans_this_month'],
                'points_this_month' => $stats['points_this_month'],
            ] as $key => $value)
                <div class="ui-card-static p-4">
                    <div class="ui-muted text-xs">{{ __('admin.merchants.stat_'.$key) }}</div>
                    <div class="mt-1 text-2xl font-bold {{ str_contains($key, 'points') ? 'text-maqam-gold-dark' : '' }}">{{ number_format($value) }}</div>
                </div>
            @endforeach
            <div class="ui-card-static p-4">
                <div class="ui-muted text-xs">{{ __('admin.merchants.stat_last_scan') }}</div>
                <div class="mt-1 text-sm font-semibold">{{ $stats['last_scan_at'] ? \Illuminate\Support\Carbon::parse($stats['last_scan_at'])->diffForHumans() : '—' }}</div>
            </div>
        </div>

        <div class="ui-card-static p-6">
            <h3 class="ui-section-title mb-3">{{ __('admin.merchants.top_customers') }}</h3>
            @forelse($topCustomers as $row)
                <div class="ui-row mb-2 text-sm">
                    <div class="min-w-0">
                        @if($row->customer)
                            <a href="{{ route('admin.customers.show', $row->customer) }}" class="font-medium hover:underline">{{ $row->customer->user?->full_name ?? '—' }}</a>
                        @else
                            <span class="font-medium">—</span>
                        @endif
                        <div class="ui-muted" dir="ltr">{{ $row->customer?->user?->phone_number }}</div>
                    </div>
                    <div class="text-end">
                        <div class="font-semibold">{{ $row->scans }} {{ __('admin.merchants.scans_unit') }}</div>
                        <div class="ui-muted">+{{ number_format((int) $row->points) }} · {{ \Illuminate\Support\Carbon::parse($row->last_scan)->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <p class="ui-muted">{{ __('admin.qr.empty_scans') }}</p>
            @endforelse
        </div>

        <div class="ui-card-static p-6">
            <h3 class="ui-section-title mb-3">{{ __('admin.merchants.scan_log') }}</h3>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.merchants.customer') }}</th>
                            <th>{{ __('admin.merchants.prize') }}</th>
                            <th>{{ __('admin.merchants.serial') }}</th>
                            <th>{{ __('admin.merchants.points') }}</th>
                            <th>{{ __('admin.merchants.scanned_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scans as $scan)
                            <tr>
                                <td>
                                    @if($scan->customer)
                                        <a href="{{ route('admin.customers.show', $scan->customer) }}" class="font-medium hover:underline">{{ $scan->customer->user?->full_name ?? '—' }}</a>
                                    @else
                                        —
                                    @endif
                                    <div class="ui-muted text-xs" dir="ltr">{{ $scan->customer?->user?->phone_number }}</div>
                                </td>
                                <td>{{ $scan->qrCode?->categoryPrize?->name_ar ?? '—' }}</td>
                                <td><code class="text-xs" dir="ltr">{{ $scan->qrCode?->serial_code }}</code></td>
                                <td class="font-semibold text-maqam-gold-dark">+{{ $scan->points_awarded_merchant }}</td>
                                <td class="whitespace-nowrap">{{ $scan->scanned_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ui-muted">{{ __('admin.qr.empty_scans') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $scans->links() }}</div>
        </div>
    </div>
</div>
@endsection
