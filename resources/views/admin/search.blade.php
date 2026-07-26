@extends('admin.layouts.app')
@section('title', __('admin.search'))
@section('content')
<form class="mb-6">
    <input name="q" value="{{ $q }}" placeholder="{{ __('admin.search_global') }}" class="ui-input !py-4 text-sm">
</form>

@if($q === '')
    <div class="ui-empty">{{ __('admin.search_global') }}</div>
@else
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="ui-card-static p-5">
            <h3 class="mb-3 ui-section-title">{{ __('admin.nav.orders_pipeline') }}</h3>
            @forelse($orders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="mb-2 ui-row !justify-start text-sm">{{ $order->order_number }}</a>
            @empty <p class="ui-muted">—</p> @endforelse
        </div>
        <div class="ui-card-static p-5">
            <h3 class="mb-3 ui-section-title">{{ __('admin.nav.products') }}</h3>
            @forelse($products ?? [] as $p)
                <a href="{{ route('admin.products.edit', $p) }}" class="mb-2 ui-row !justify-start text-sm">{{ $p->name_ar ?: $p->name_en }}</a>
            @empty <p class="ui-muted">—</p> @endforelse
        </div>
        <div class="ui-card-static p-5">
            <h3 class="mb-3 ui-section-title">{{ __('admin.customers.title') }}</h3>
            @forelse($customers as $c)
                <a href="{{ route('admin.customers.show', $c) }}" class="mb-2 ui-row !justify-start text-sm">{{ $c->user?->full_name }}</a>
            @empty <p class="ui-muted">—</p> @endforelse
        </div>
        <div class="ui-card-static p-5">
            <h3 class="mb-3 ui-section-title">{{ __('admin.merchants.title') }}</h3>
            @forelse($merchants as $m)
                <a href="{{ route('admin.merchants.inbox', ['review' => $m->id]) }}" class="mb-2 ui-row !justify-start text-sm">{{ $m->business_name }} <code class="text-xs" dir="ltr">{{ $m->merchant_code }}</code></a>
            @empty <p class="ui-muted">—</p> @endforelse
        </div>
        <div class="ui-card-static p-5 lg:col-span-2">
            <h3 class="mb-3 ui-section-title">QR / {{ __('admin.qr.batch_id') }}</h3>
            @forelse($codes as $code)
                <a href="{{ route('admin.qr-codes.index', ['batch_id' => $code->batch_id]) }}" class="mb-2 ui-row !justify-start font-mono text-xs" dir="ltr">{{ $code->serial_code }} · {{ $code->batch_id }}</a>
            @empty <p class="ui-muted">—</p> @endforelse
        </div>
    </div>
@endif
@endsection
