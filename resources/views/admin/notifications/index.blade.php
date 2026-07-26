@extends('admin.layouts.app')
@section('title', __('admin.notifications.history'))
@section('actions')
<a href="{{ route('admin.notifications.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.notifications.composer') }}</a>
@endsection
@section('content')
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
