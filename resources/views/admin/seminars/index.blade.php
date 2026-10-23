@extends('admin.layouts.app')
@section('title', __('admin.seminars.title'))
@section('subtitle', __('admin.seminars.subtitle'))
@section('actions')
<a href="{{ route('admin.seminars.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.seminars.new') }}</a>
@endsection
@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

@if($seminars->isEmpty())
    <div class="ui-empty">
        <p class="font-semibold">{{ __('admin.seminars.empty') }}</p>
        <p class="ui-muted mt-2">{{ __('admin.seminars.empty_hint') }}</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($seminars as $seminar)
            @php($status = $seminar->status())
            <a href="{{ route('admin.seminars.show', $seminar) }}" class="ui-card block px-5 py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold">{{ $seminar->title }}</div>
                        <div class="ui-muted mt-1 text-sm">
                            {{ $seminar->starts_at->timezone(\App\Http\Controllers\Admin\SeminarController::TIMEZONE)->translatedFormat('j M Y · H:i') }}
                            —
                            {{ $seminar->ends_at->timezone(\App\Http\Controllers\Admin\SeminarController::TIMEZONE)->translatedFormat('j M Y · H:i') }}
                        </div>
                        <div class="ui-muted mt-2 text-sm">
                            {{ __('admin.seminars.attendees_count', ['count' => $seminar->registrations_count]) }}
                            · {{ __('admin.seminars.draws_progress', ['drawn' => $seminar->drawn_count, 'total' => $seminar->draws_count]) }}
                        </div>
                    </div>
                    <span class="ui-badge {{ ['live' => 'ui-badge-ok', 'upcoming' => 'ui-badge-gold', 'ended' => 'ui-badge-muted'][$status] }}">
                        {{ __('admin.seminars.status_'.$status) }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $seminars->links() }}</div>
@endif
@endsection
