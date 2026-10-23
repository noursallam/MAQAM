@extends('admin.layouts.app')
@php
    $tz = \App\Http\Controllers\Admin\SeminarController::TIMEZONE;
    $status = $seminar->status();
@endphp
@section('title', $seminar->title)
@section('subtitle', $seminar->starts_at->timezone($tz)->translatedFormat('j M Y · H:i').' — '.$seminar->ends_at->timezone($tz)->translatedFormat('j M Y · H:i'))
@section('actions')
<a href="{{ route('admin.seminars.edit', $seminar) }}" class="ui-btn ui-btn-ghost">{{ __('admin.seminars.edit') }}</a>
@endsection
@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <div class="ui-card-static p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="ui-section-title">{{ __('admin.seminars.draws') }}</h3>
                    <p class="ui-muted text-sm">{{ __('admin.seminars.draws_progress', ['drawn' => $seminar->draws->whereNotNull('drawn_at')->count(), 'total' => $seminar->draws->count()]) }}</p>
                </div>
                @if($remaining > 0 && $status !== 'upcoming')
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.seminars.draw', $seminar) }}">
                            @csrf
                            <button class="ui-btn ui-btn-primary">{{ __('admin.seminars.draw_next') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.seminars.draw-all', $seminar) }}"
                              onsubmit="return confirm(@js(__('admin.seminars.draw_all_confirm', ['count' => $remaining])))">
                            @csrf
                            <button class="ui-btn ui-btn-ghost">{{ __('admin.seminars.draw_all') }}</button>
                        </form>
                    </div>
                @elseif($status === 'upcoming')
                    <span class="ui-badge ui-badge-gold">{{ __('admin.seminars.draw_waits_for_start') }}</span>
                @endif
            </div>
            <div class="ui-table-wrap mt-4">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('admin.seminars.prize') }}</th>
                            <th>{{ __('admin.seminars.winner') }}</th>
                            <th>{{ __('admin.seminars.drawn_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seminar->draws as $draw)
                            <tr>
                                <td>{{ $draw->position }}</td>
                                <td class="font-medium">{{ $draw->prize }}</td>
                                <td>
                                    @if($draw->winner)
                                        <div class="font-semibold">{{ $draw->winner->full_name }}</div>
                                        <div class="ui-muted text-xs" dir="ltr">{{ $draw->winner->phone_number }}</div>
                                    @else
                                        <span class="ui-muted">{{ __('admin.seminars.not_drawn') }}</span>
                                    @endif
                                </td>
                                <td class="ui-muted text-sm">{{ $draw->drawn_at?->timezone($tz)->format('H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ui-card-static p-5">
            <h3 class="ui-section-title mb-4">{{ __('admin.seminars.attendees_count', ['count' => $attendees->total()]) }}</h3>
            @if($attendees->isEmpty())
                <p class="ui-muted text-sm">{{ __('admin.seminars.no_attendees') }}</p>
            @else
                <div class="ui-table-wrap">
                    <table class="ui-table">
                        <tbody>
                            @foreach($attendees as $registration)
                                <tr>
                                    <td class="font-medium">{{ $registration->user?->full_name }}</td>
                                    <td dir="ltr" class="ui-muted">{{ $registration->user?->phone_number }}</td>
                                    <td class="ui-muted text-sm">{{ $registration->created_at->timezone($tz)->format('H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $attendees->links() }}</div>
            @endif
        </div>
    </div>

    <aside class="space-y-4">
        <div class="ui-card-static overflow-hidden p-0">
            @if($seminar->imageUrl())
                <img src="{{ $seminar->imageUrl() }}" alt="" class="aspect-[16/9] w-full object-cover">
            @endif
            <div class="space-y-2 p-5">
                <span class="ui-badge {{ ['live' => 'ui-badge-ok', 'upcoming' => 'ui-badge-gold', 'ended' => 'ui-badge-muted'][$status] }}">
                    {{ __('admin.seminars.status_'.$status) }}
                </span>
                @if($seminar->location)
                    <p class="text-sm">{{ $seminar->location }}</p>
                @endif
                @if($seminar->description)
                    <p class="ui-muted whitespace-pre-line text-sm">{{ $seminar->description }}</p>
                @endif
                <p class="ui-muted text-xs">{{ __('admin.seminars.app_visibility') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.seminars.destroy', $seminar) }}"
              onsubmit="return confirm(@js(__('admin.seminars.delete_confirm')))">
            @csrf
            @method('DELETE')
            <button class="ui-btn ui-btn-ghost w-full text-red-700">{{ __('admin.seminars.delete') }}</button>
        </form>
    </aside>
</div>
@endsection
