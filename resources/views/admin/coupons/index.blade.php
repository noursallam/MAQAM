@extends('admin.layouts.app')
@section('title', __('admin.coupons.title'))
@section('actions')
<a href="{{ route('admin.coupons.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.coupons.add') }}</a>
@endsection
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($coupons as $coupon)
        <div class="ui-card p-5">
            <div class="flex items-start justify-between">
                <code class="text-lg font-semibold" dir="ltr">{{ $coupon->code }}</code>
                <span class="ui-badge {{ $coupon->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">{{ $coupon->is_active ? __('admin.active') : __('admin.inactive') }}</span>
            </div>
            <div class="mt-3 text-sm">
                <div>{{ __('admin.coupons.value') }}: <strong>{{ $coupon->type === 'percentage' ? $coupon->value.'%' : number_format($coupon->value,2).' ج.م' }}</strong></div>
                <div class="ui-muted mt-1">{{ __('admin.coupons.valid') }}: {{ $coupon->valid_from?->format('Y-m-d') }} → {{ $coupon->valid_to?->format('Y-m-d') }}</div>
                <div class="ui-muted mt-1">{{ __('admin.coupons.usage') }}: {{ $coupon->used_count }}{{ $coupon->usage_limit ? '/'.$coupon->usage_limit : '' }}</div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="ui-btn ui-btn-ghost text-xs">{{ __('admin.edit') }}</a>
                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm(@json(__('admin.confirm_delete')))">@csrf @method('DELETE')<button class="ui-btn ui-btn-ghost text-xs text-red-700">{{ __('admin.delete') }}</button></form>
            </div>
        </div>
    @empty
        <div class="ui-empty col-span-full">
            <p class="ui-muted">{{ __('admin.empty_title') }}</p>
            <a href="{{ route('admin.coupons.create') }}" class="ui-btn ui-btn-primary mt-4">{{ __('admin.coupons.add') }}</a>
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $coupons->links() }}</div>
@endsection
