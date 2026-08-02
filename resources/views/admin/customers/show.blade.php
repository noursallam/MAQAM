@extends('admin.layouts.app')

@section('title', __('admin.customers.profile').' — '.$customer->user?->full_name)
@section('breadcrumbs')
<a href="{{ route('admin.customers.index') }}" class="hover:text-maqam-ink">{{ __('admin.customers.title') }}</a>
<span class="mx-1">/</span>
<span>{{ $customer->user?->full_name }}</span>
@endsection

@section('actions')
<a href="{{ route('admin.customers.edit', $customer) }}" class="ui-btn ui-btn-ghost">{{ __('admin.edit') }}</a>
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-1">
        <div class="ui-card-static p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ $customer->user?->full_name }}</h2>
                    <div class="ui-muted mt-1" dir="ltr">{{ $customer->user?->phone_number }}</div>
                    <div class="ui-muted">{{ $customer->user?->email }}</div>
                </div>
                <span class="ui-badge {{ $customer->user?->is_active ? 'ui-badge-ok' : 'ui-badge-danger' }}">
                    {{ $customer->user?->is_active ? __('admin.active') : __('admin.customers.frozen') }}
                </span>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex justify-between text-sm">
                    <span>{{ $customer->rank?->name_ar ?? '—' }}</span>
                    @if($nextRank)
                        <span class="ui-muted">← {{ $nextRank->name_ar }}</span>
                    @endif
                </div>
                <div class="h-3 overflow-hidden rounded bg-maqam-soft">
                    <div class="h-full rounded bg-maqam-gold transition-all" style="width: {{ $progress }}%"></div>
                </div>
                <div class="ui-muted mt-2">
                    {{ __('admin.customers.rank_progress') }}
                    @if($nextRank)
                        — {{ max(0, $nextRank->min_points - $customer->points_balance) }} {{ __('admin.customers.to_next') }}
                    @endif
                </div>
            </div>

            <div class="mt-6 grid grid-cols-3 gap-2 text-center">
                <div class="ui-card-soft p-3">
                    <div class="ui-muted">{{ __('admin.customers.points_balance') }}</div>
                    <div class="font-semibold text-maqam-gold-dark">{{ number_format($customer->points_balance) }}</div>
                </div>
                <div class="ui-card-soft p-3">
                    <div class="ui-muted">{{ __('admin.customers.earned') }}</div>
                    <div class="font-semibold">{{ number_format($customer->total_points_earned) }}</div>
                </div>
                <div class="ui-card-soft p-3">
                    <div class="ui-muted">{{ __('admin.customers.spent') }}</div>
                    <div class="font-semibold">{{ number_format($customer->total_points_spent) }}</div>
                </div>
            </div>
        </div>

        <div class="ui-card-static space-y-4 p-6">
            <h3 class="ui-section-title">{{ __('admin.actions') }}</h3>
            <form method="POST" action="{{ route('admin.customers.adjust-points', $customer) }}" class="space-y-2">
                @csrf
                <label class="ui-muted">{{ __('admin.customers.adjust_points') }}</label>
                <input type="number" name="amount" required placeholder="±" class="ui-input">
                <input type="text" name="reason" required placeholder="{{ __('admin.customers.adjust_reason') }}" class="ui-input">
                <button class="ui-btn ui-btn-dark ui-btn-block">{{ __('admin.confirm') }}</button>
            </form>

            @if($customer->user?->is_active)
                <form method="POST" action="{{ route('admin.customers.freeze', $customer) }}" onsubmit="return confirm(@json(__('admin.confirm')))">
                    @csrf
                    <button class="ui-btn ui-btn-block border-red-300 bg-red-50 text-red-700 hover:border-red-400">{{ __('admin.customers.freeze') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.customers.unfreeze', $customer) }}">
                    @csrf
                    <button class="ui-btn ui-btn-block border-emerald-300 bg-emerald-50 text-emerald-700 hover:border-emerald-400">{{ __('admin.customers.unfreeze') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="space-y-4 xl:col-span-2">
        <div class="ui-card-static p-6">
            <h3 class="ui-section-title mb-3">{{ __('admin.customers.scan_history') }}</h3>
            <div class="space-y-2">
                @forelse($scans as $scan)
                    <div class="ui-row text-sm">
                        <div>
                            <div class="font-mono text-xs" dir="ltr">{{ $scan->qrCode?->serial_code }}</div>
                            <div class="ui-muted">{{ $scan->merchant?->business_name ?? __('admin.qr.no_merchant') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="font-semibold text-maqam-gold-dark">+{{ $scan->points_awarded_customer }}</div>
                            <div class="ui-muted">{{ $scan->scanned_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="ui-muted">{{ __('admin.qr.empty_scans') }}</p>
                @endforelse
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="ui-card-static p-6">
                <h3 class="ui-section-title mb-3">{{ __('admin.customers.ledger') }}</h3>
                @forelse($ledger as $tx)
                    <div class="ui-row mb-2 text-sm">
                        <span>{{ $tx->description ?: $tx->type }}</span>
                        <span class="font-semibold {{ $tx->amount >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}</span>
                    </div>
                @empty
                    <p class="ui-muted">—</p>
                @endforelse
            </div>
            <div class="ui-card-static p-6">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="ui-section-title">{{ __('admin.customers.rewards_wallet') }}</h3>
                    <a href="{{ route('admin.rewards.index', ['q' => $customer->user?->phone_number]) }}" class="text-xs font-semibold text-maqam-gold-dark">{{ __('admin.view_all') }}</a>
                </div>
                @forelse($rewards as $reward)
                    <div class="ui-row mb-2 text-sm">
                        <div class="min-w-0">
                            <div class="font-medium">{{ __('admin.rewards.type_'.$reward->type) }}
                                <code class="ms-1 text-xs" dir="ltr">{{ $reward->code }}</code>
                            </div>
                            <div class="ui-muted">
                                @if($reward->type === 'product')
                                    {{ $reward->product?->name_ar ?: $reward->product?->name_en }}
                                @elseif($reward->amount_type === 'percentage')
                                    {{ $reward->amount_value }}%
                                @elseif($reward->amount_value !== null)
                                    {{ number_format((float) $reward->amount_value, 2) }} ج.م
                                @endif
                                · {{ __('admin.rewards.source_'.$reward->source) }}
                            </div>
                        </div>
                        <span class="ui-badge {{ $reward->status === 'available' ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                            {{ __('admin.rewards.status_'.$reward->status) }}
                        </span>
                    </div>
                @empty
                    <p class="ui-muted">—</p>
                @endforelse
            </div>
            <div class="ui-card-static p-6 md:col-span-2">
                <h3 class="ui-section-title mb-3">{{ __('admin.customers.orders') }} / {{ __('admin.customers.wheel_spins') }}</h3>
                @foreach($orders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="ui-row mb-2 text-sm">
                        <span>{{ $order->order_number }}</span>
                        <span>{{ number_format($order->total_amount, 0) }}</span>
                    </a>
                @endforeach
                @foreach($spins as $spin)
                    <div class="ui-row mb-2 text-sm">
                        <span>
                            {{ $spin->is_win ? __('admin.wheel.is_win') : __('admin.wheel.is_loss') }}
                            @if($spin->reward)
                                · <code dir="ltr">{{ $spin->reward->code }}</code>
                            @endif
                        </span>
                        <span>{{ $spin->prize_type }}</span>
                    </div>
                @endforeach
                @if($orders->isEmpty() && $spins->isEmpty())
                    <p class="ui-muted">—</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
