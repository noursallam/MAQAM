@extends('admin.layouts.app')

@section('title', __('admin.orders.title'))
@section('subtitle', __('admin.orders.subtitle'))

@section('actions')
<div class="flex overflow-hidden rounded-[0.625rem] border border-[#D8D4CB] bg-white text-sm font-semibold">
    <a href="{{ route('admin.orders.index', ['view' => 'kanban']) }}" class="px-4 py-2 {{ ($viewMode ?? 'kanban') === 'kanban' ? 'bg-maqam-gold text-maqam-navy' : 'ui-muted' }}">{{ __('admin.orders.kanban') }}</a>
    <a href="{{ route('admin.orders.index', ['view' => 'table']) }}" class="px-4 py-2 {{ ($viewMode ?? '') === 'table' ? 'bg-maqam-gold text-maqam-navy' : 'ui-muted' }}">{{ __('admin.orders.table') }}</a>
</div>
@endsection

@section('content')
@php
    $statusLabels = [
        'new' => __('admin.orders.new'),
        'processing' => __('admin.orders.processing'),
        'shipped' => __('admin.orders.shipped'),
        'delivered' => __('admin.orders.delivered'),
        'cancelled' => __('admin.orders.cancelled'),
        'refunded' => __('admin.orders.refunded'),
    ];
    $statusColors = [
        'new' => 'bg-sky-100 text-sky-800 border-sky-200',
        'processing' => 'bg-amber-100 text-amber-800 border-amber-200',
        'shipped' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'delivered' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'cancelled' => 'bg-red-100 text-red-800 border-red-200',
        'refunded' => 'bg-gray-200 text-gray-700 border-gray-300',
    ];
    $paymentMethodIcons = [
        'credit' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
        'cod' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    ];
    $paymentMethodColors = [
        'credit' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'cod' => 'bg-orange-100 text-orange- 700 border-orange-200',
    ];
@endphp

@if(($viewMode ?? 'kanban') === 'kanban')
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($columns as $status => $list)
            <div class="w-72 shrink-0 ui-card-static p-3">
                <div class="mb-3 flex items-center justify-between px-1">
                    <span class="ui-badge {{ $statusColors[$status] }}">{{ $statusLabels[$status] }}</span>
                    <span class="ui-muted">{{ $list->count() }}</span>
                </div>
                <div class="space-y-2 max-h-[70vh] overflow-y-auto">
                    @forelse($list as $order)
                        @php
                            $paymentMethod = strtolower($order->payment_method ?? 'credit');
                            $paymentIcon = $paymentMethodIcons[$paymentMethod] ?? $paymentMethodIcons['credit'];
                            $paymentColor = $paymentMethodColors[$paymentMethod] ?? $paymentMethodColors['credit'];
                        @endphp
                        <a href="{{ route('admin.orders.show', $order) }}" class="block rounded-xl border border-[#D8D4CB] bg-white p-3 transition hover:border-maqam-gold hover:shadow-sm">
                            <div class="flex justify-between text-sm font-semibold">
                                <span>{{ $order->order_number }}</span>
                                <span>{{ number_format($order->total_amount, 0) }} ج.م</span>
                            </div>
                            <div class="mt-1 ui-muted">{{ $order->user?->full_name }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium uppercase {{ $paymentColor }}">
                                    {!! $paymentIcon !!}
                                    <span>{{ $order->payment_method }}</span>
                                </span>
                                <span class="text-[10px] uppercase ui-muted">{{ $order->payment_status }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="ui-empty !py-8 text-xs">{{ __('admin.orders.empty') }}</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@else
    <form class="ui-toolbar">
        <input type="hidden" name="view" value="table">
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.orders.order_number') }}" class="ui-input max-w-xs">
        <select name="status" class="ui-select max-w-xs" onchange="this.form.submit()">
            <option value="">{{ __('admin.all') }}</option>
            @foreach($statusLabels as $st => $label)
                <option value="{{ $st }}" @selected(request('status')===$st)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
            <tr>
                <th>{{ __('admin.orders.order_number') }}</th>
                <th>{{ __('admin.orders.customer') }}</th>
                <th>{{ __('admin.orders.total') }}</th>
                <th>{{ __('admin.orders.payment') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($orders as $order)
                @php
                    $paymentMethod = strtolower($order->payment_method ?? 'credit');
                    $paymentIcon = $paymentMethodIcons[$paymentMethod] ?? $paymentMethodIcons['credit'];
                    $paymentColor = $paymentMethodColors[$paymentMethod] ?? $paymentMethodColors['credit'];
                @endphp
                <tr>
                    <td class="font-medium">{{ $order->order_number }}</td>
                    <td>{{ $order->user?->full_name }}</td>
                    <td>{{ number_format($order->total_amount, 0) }} ج.م</td>
                    <td>
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium uppercase {{ $paymentColor }}">
                            {!! $paymentIcon !!}
                            <span>{{ $order->payment_method }}</span>
                        </span>
                        <span class="ml-1 text-xs text-maqam-muted">{{ $order->payment_status }}</span>
                    </td>
                    <td><span class="ui-badge {{ $statusColors[$order->status] ?? 'ui-badge-muted' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="text-maqam-gold-dark font-semibold text-sm">{{ __('admin.view') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="px-5 py-4">{{ $orders->links() }}</div>
    </div>
@endif
@endsection
