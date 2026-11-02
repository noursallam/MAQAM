@extends('admin.layouts.app')
@section('title', __('admin.merchant_change.title'))
@section('content')
<div class="ui-table-wrap">
    <table class="ui-table">
        <thead>
            <tr>
                <th>{{ __('admin.merchant_change.user') }}</th>
                <th>{{ __('admin.merchant_change.current') }}</th>
                <th>{{ __('admin.merchant_change.requested') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.merchant_change.date') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <div class="font-semibold">{{ $req->user?->full_name }}</div>
                        <div class="ui-muted text-xs" dir="ltr">{{ $req->user?->phone_number }}</div>
                    </td>
                    <td>{{ $req->currentMerchant?->business_name ?? '—' }}</td>
                    <td>
                        <div>{{ $req->requestedMerchant?->business_name ?? $req->requested_merchant_code }}</div>
                        <code class="text-xs ui-muted" dir="ltr">{{ $req->requested_merchant_code }}</code>
                    </td>
                    <td>
                        <span class="ui-badge {{ $req->status === 'approved' ? 'ui-badge-ok' : ($req->status === 'rejected' ? 'ui-badge-danger' : 'ui-badge-warn') }}">
                            {{ __('admin.merchant_change.status_' . $req->status) }}
                        </span>
                    </td>
                    <td class="ui-muted text-xs">{{ $req->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($req->status === 'pending')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.merchant-change-requests.approve', $req) }}">
                                    @csrf
                                    <button class="ui-btn ui-btn-primary text-xs">{{ __('admin.merchants.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.merchant-change-requests.reject', $req) }}">
                                    @csrf
                                    <button class="ui-btn ui-btn-danger text-xs">{{ __('admin.merchants.reject') }}</button>
                                </form>
                            </div>
                        @else
                            <span class="ui-muted text-xs">{{ $req->reviewer?->user?->full_name }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="ui-empty">{{ __('admin.empty_title') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
