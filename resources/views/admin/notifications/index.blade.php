@extends('admin.layouts.app')
@section('title', __('admin.notifications.history'))
@section('actions')
<a href="{{ route('admin.notifications.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.notifications.composer') }}</a>
@endsection
@section('content')
<div class="ui-card-static mb-6 p-5">
    <h3 class="ui-section-title mb-4">{{ __('admin.notifications.health') }}</h3>
    @if($health['stuck'])
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ __('admin.notifications.queue_stuck', ['since' => $health['oldest_waiting']->diffForHumans()]) }}
        </div>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="ui-muted text-xs">{{ __('admin.notifications.firebase') }}</div>
            <span class="ui-badge {{ $health['configured'] ? 'ui-badge-ok' : 'ui-badge-danger' }} mt-1">
                {{ $health['configured'] ? __('admin.notifications.configured') : __('admin.notifications.not_configured') }}
            </span>
        </div>
        <div>
            <div class="ui-muted text-xs">{{ __('admin.notifications.devices') }}</div>
            <div class="mt-1 font-semibold">{{ number_format($health['devices']) }}</div>
            <div class="ui-muted text-xs">{{ __('admin.notifications.devices_detail', ['count' => number_format($health['users_with_devices'])]) }}</div>
        </div>
        <div>
            <div class="ui-muted text-xs">{{ __('admin.notifications.queue') }}</div>
            <div class="mt-1 font-semibold">
                {{ $health['waiting'] ? number_format($health['waiting']) : __('admin.notifications.queue_empty') }}
            </div>
            @if($health['failed_jobs'])
                <span class="ui-badge ui-badge-warn mt-1">{{ __('admin.notifications.failed_jobs') }}: {{ $health['failed_jobs'] }}</span>
            @endif
        </div>
        <div>
            <div class="ui-muted text-xs">{{ __('admin.notifications.last_run') }}</div>
            @if($health['last_run'])
                <div class="mt-1 font-semibold">{{ $health['last_run_at']->diffForHumans() }}</div>
                <div class="ui-muted text-xs">{{ __('admin.notifications.last_run_detail', $health['last_run']) }}</div>
            @else
                <div class="mt-1 ui-muted">{{ __('admin.notifications.never') }}</div>
            @endif
        </div>
    </div>
</div>
<div class="space-y-3">
    @forelse($notifications as $n)
        <div class="ui-card-static px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="font-semibold">{{ $n->title }}</div>
                    <div class="mt-1 text-sm ui-muted">{{ $n->body }}</div>
                    <div class="mt-2 ui-muted">{{ $n->user?->full_name }} · {{ __('admin.notifications.'.$n->type) }}</div>
                </div>
                <span class="ui-badge {{ $n->is_read ? 'ui-badge-muted' : 'ui-badge-gold' }}">
                    {{ $n->is_read ? __('admin.notifications.read') : __('admin.notifications.unread') }}
                </span>
            </div>
        </div>
    @empty
        <div class="ui-empty">{{ __('admin.empty_title') }}</div>
    @endforelse
</div>
<div class="mt-6">{{ $notifications->links() }}</div>
@endsection
