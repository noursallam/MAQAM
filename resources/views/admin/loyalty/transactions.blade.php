@extends('admin.layouts.app')
@section('title', __('admin.loyalty.transactions'))
@section('content')
<form class="ui-toolbar">
    <select name="type" class="ui-select max-w-xs" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        @foreach(['earn','spend','refund','expire','adjust'] as $type)
            <option value="{{ $type }}" @selected(request('type')===$type)>{{ $type }}</option>
        @endforeach
    </select>
</form>
<div class="ui-table-wrap">
    <table class="ui-table">
        <thead><tr>
            <th>التاريخ</th>
            <th>{{ __('admin.orders.customer') }}</th>
            <th>{{ __('admin.loyalty.type') }}</th>
            <th>{{ __('admin.loyalty.amount') }}</th>
            <th>{{ __('admin.loyalty.balance_after') }}</th>
            <th>{{ __('admin.loyalty.description') }}</th>
        </tr></thead>
        <tbody>
        @forelse($transactions as $tx)
            <tr>
                <td class="text-xs">{{ $tx->transaction_date?->format('Y-m-d H:i') }}</td>
                <td>{{ $tx->customer?->user?->full_name }}</td>
                <td>{{ $tx->type }}</td>
                <td class="font-semibold {{ $tx->amount >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $tx->amount }}</td>
                <td>{{ $tx->balance_after }}</td>
                <td class="ui-muted">{{ $tx->description }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-10 text-center ui-muted">{{ __('admin.empty_title') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4">{{ $transactions->links() }}</div>
</div>
@endsection
