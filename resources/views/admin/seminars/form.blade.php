@extends('admin.layouts.app')
@php
    $tz = \App\Http\Controllers\Admin\SeminarController::TIMEZONE;
    $editing = $seminar->exists;
    $locked = $editing && $seminar->hasDrawn();
@endphp
@section('title', $editing ? __('admin.seminars.edit') : __('admin.seminars.new'))
@section('subtitle', __('admin.seminars.form_subtitle'))
@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
@endif

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('admin.seminars.update', $seminar) : route('admin.seminars.store') }}"
      class="grid gap-6 lg:grid-cols-2">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="ui-card-static space-y-4 p-6">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.name') }} *</label>
            <input name="title" value="{{ old('title', $seminar->title) }}" required maxlength="255" class="ui-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.description') }}</label>
            <textarea name="description" rows="4" class="ui-textarea">{{ old('description', $seminar->description) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.location') }}</label>
            <input name="location" value="{{ old('location', $seminar->location) }}" maxlength="255" class="ui-input">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.starts_at') }} *</label>
                <input type="datetime-local" name="starts_at" required class="ui-input" dir="ltr"
                       value="{{ old('starts_at', $seminar->starts_at?->timezone($tz)->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.ends_at') }} *</label>
                <input type="datetime-local" name="ends_at" required class="ui-input" dir="ltr"
                       value="{{ old('ends_at', $seminar->ends_at?->timezone($tz)->format('Y-m-d\TH:i')) }}">
            </div>
        </div>
        <p class="ui-muted text-xs">{{ __('admin.seminars.times_hint') }}</p>
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.image') }}</label>
            @if($seminar->imageUrl())
                <img src="{{ $seminar->imageUrl() }}" alt="" class="mb-2 aspect-[16/9] w-full rounded-xl object-cover">
            @endif
            <input type="file" name="image" accept="image/*" class="ui-input">
            <p class="ui-muted mt-1 text-xs">{{ __('admin.seminars.image_hint') }}</p>
        </div>
    </div>

    <div class="ui-card-static space-y-4 p-6">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ __('admin.seminars.prizes') }} *</label>
            @if($locked)
                <ol class="list-decimal space-y-1 ps-5 text-sm">
                    @foreach(explode("\n", $prizes) as $prize)
                        <li>{{ $prize }}</li>
                    @endforeach
                </ol>
                <p class="ui-muted mt-2 text-xs">{{ __('admin.seminars.prizes_locked') }}</p>
            @else
                <textarea name="prizes" id="prizes" rows="10" required class="ui-textarea"
                          placeholder="{{ __('admin.seminars.prizes_placeholder') }}">{{ old('prizes', $prizes) }}</textarea>
                <p class="ui-muted mt-1 text-xs">{{ __('admin.seminars.prizes_hint') }}</p>
                <p class="mt-2 text-sm font-medium" id="drawCount"></p>
            @endif
        </div>
        <button class="ui-btn ui-btn-primary w-full">{{ $editing ? __('admin.seminars.save') : __('admin.seminars.create') }}</button>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        const prizes = document.getElementById('prizes');
        const count = document.getElementById('drawCount');
        if (!prizes) return;
        const label = @json(__('admin.seminars.draws_count_live'));
        const update = () => {
            const n = prizes.value.split(/\r?\n/).filter(line => line.trim() !== '').length;
            count.textContent = label.replace(':count', n);
        };
        prizes.addEventListener('input', update);
        update();
    })();
</script>
@endpush
@endsection
