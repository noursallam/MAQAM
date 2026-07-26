@extends('admin.layouts.app')

@section('title', __('admin.merchants.inbox_title'))
@section('subtitle', __('admin.merchants.inbox_subtitle'))

@section('actions')
<a href="{{ route('admin.merchants.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.merchants.add') }}</a>
@endsection

@section('content')
<div class="mb-4">
    <span class="ui-badge ui-badge-warn">{{ $pending->total() }} {{ __('admin.merchants.pending_count') }}</span>
</div>

<div class="grid gap-6 xl:grid-cols-12">
    {{-- Inbox list --}}
    <div class="space-y-3 xl:col-span-4">
        <h2 class="ui-section-title ui-muted !text-sm">{{ __('admin.pending') }}</h2>
        @forelse($pending as $m)
            <a href="{{ route('admin.merchants.inbox', ['review' => $m->id]) }}"
               class="block p-4 transition {{ optional($selected)->id === $m->id ? 'ui-card border-maqam-gold ring-2 ring-maqam-gold/40' : 'ui-card' }}">
                <div class="font-semibold">{{ $m->business_name }}</div>
                <div class="mt-1 ui-muted">{{ $m->user?->full_name }} · {{ $m->user?->phone_number }}</div>
                <div class="mt-2 text-[11px] ui-muted">{{ $m->created_at?->diffForHumans() }}</div>
            </a>
        @empty
            <div class="ui-empty text-sm">{{ __('admin.merchants.empty_inbox') }}</div>
        @endforelse
        <div>{{ $pending->links() }}</div>
    </div>

    {{-- Review panel --}}
    <div class="xl:col-span-5">
        @if($selected)
            <div class="ui-card-static p-6">
                <div class="mb-1 text-xs font-semibold uppercase tracking-wide ui-muted">{{ __('admin.merchants.review') }}</div>
                <h2 class="text-xl font-semibold">{{ $selected->business_name }}</h2>
                <div class="mt-6 space-y-4 text-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] text-2xl text-maqam-gold">
                            @if($selected->logo_url)
                                <img src="{{ $selected->logo_url }}" alt="" class="h-16 w-16 rounded-xl object-cover">
                            @else
                                م
                            @endif
                        </div>
                        <div>
                            <div class="ui-muted">{{ __('admin.merchants.owner') }}</div>
                            <div class="font-medium">{{ $selected->user?->full_name }}</div>
                            <div class="ui-muted">{{ $selected->user?->phone_number }}</div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4">
                        <div class="ui-muted">{{ __('admin.merchants.address') }}</div>
                        <div class="mt-1">{{ $selected->business_address ?: '—' }}</div>
                    </div>
                    <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4">
                        <div class="ui-muted">{{ __('admin.merchants.merchant_code') }}</div>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <code class="text-lg font-semibold" dir="ltr">{{ $selected->merchant_code }}</code>
                            <button type="button" onclick="copyText(@json($selected->merchant_code))" class="ui-btn ui-btn-dark text-xs !px-3 !py-1.5">{{ __('admin.merchants.copy_code') }}</button>
                        </div>
                    </div>
                </div>

                @unless($selected->is_approved)
                    <div class="mt-6 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.merchants.approve', $selected) }}">
                            @csrf
                            <button class="ui-btn ui-btn-primary !bg-emerald-600 !border-emerald-600 !text-white hover:!bg-emerald-700">{{ __('admin.merchants.approve') }}</button>
                        </form>
                        <button type="button" onclick="document.getElementById('rejectBox').classList.toggle('hidden')" class="ui-btn border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">{{ __('admin.merchants.reject') }}</button>
                    </div>
                    <form id="rejectBox" method="POST" action="{{ route('admin.merchants.reject', $selected) }}" class="mt-4 hidden space-y-3 rounded-xl border border-red-200 bg-red-50 p-4">
                        @csrf
                        <label class="block text-sm font-medium text-red-800">{{ __('admin.merchants.reject_reason') }}</label>
                        <textarea name="reason" required rows="3" class="ui-textarea"></textarea>
                        <button class="ui-btn ui-btn-dark !bg-red-600 !border-red-600">{{ __('admin.confirm') }} {{ __('admin.merchants.reject') }}</button>
                    </form>
                @else
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ __('admin.approved') }} · {{ $selected->approved_at?->format('Y-m-d H:i') }}
                    </div>
                @endunless
            </div>
        @else
            <div class="ui-empty flex h-full min-h-64 items-center justify-center">
                {{ __('admin.merchants.empty_inbox') }}
            </div>
        @endif
    </div>

    {{-- Approved directory --}}
    <div class="xl:col-span-3">
        <h2 class="mb-3 ui-section-title ui-muted !text-sm">{{ __('admin.merchants.directory') }}</h2>
        <div class="space-y-2">
            @forelse($approved as $m)
                <div class="ui-card-static p-3 text-sm">
                    <div class="font-medium">{{ $m->business_name }}</div>
                    <div class="mt-1 flex items-center justify-between">
                        <code class="text-[11px] text-maqam-gold-dark" dir="ltr">{{ $m->merchant_code }}</code>
                        <button type="button" onclick="copyText(@json($m->merchant_code))" class="text-[10px] ui-muted underline">{{ __('admin.copy') }}</button>
                    </div>
                    @if($m->approvedBy)
                        <div class="mt-1 text-[10px] ui-muted">{{ __('admin.merchants.approved_by') }}: {{ $m->approvedBy->user?->full_name ?? 'admin' }}</div>
                    @endif
                </div>
            @empty
                <p class="ui-muted">—</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $approved->links() }}</div>
    </div>
</div>
@endsection
