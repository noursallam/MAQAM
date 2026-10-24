@extends('admin.layouts.app')

@section('title', __('admin.app_texts.title'))

@section('content')
<p class="ui-muted mb-4 max-w-3xl">{{ __('admin.app_texts.intro', ['count' => $changedCount]) }}</p>

<form class="ui-toolbar">
    <input name="q" value="{{ $search }}" placeholder="{{ __('admin.app_texts.search') }}" class="ui-input max-w-md">
    @if($search !== '')
        <a href="{{ route('admin.app-texts.index') }}" class="ui-btn ui-btn-ghost">{{ __('admin.app_texts.clear_search') }}</a>
    @endif
</form>

<div class="grid gap-6 lg:grid-cols-4">
    <nav class="ui-card-static h-max p-2 lg:sticky lg:top-4">
        @foreach($groups as $name => $count)
            <a href="{{ route('admin.app-texts.index', ['group' => $name]) }}"
               class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ $group === $name ? 'bg-maqam-navy font-semibold text-white' : 'hover:bg-maqam-soft' }}">
                <span>{{ __('admin.app_texts.groups.'.$name) }}</span>
                <span class="text-xs opacity-70">{{ $count }}</span>
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.app-texts.update') }}" class="space-y-3 lg:col-span-3">
        @csrf @method('PUT')
        @forelse($texts as $key => $text)
            @php $row = $overrides[$key] ?? null; @endphp
            <div class="ui-card-static p-4 {{ $row ? 'border-maqam-gold' : '' }}">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <code class="text-xs ui-muted" dir="ltr">{{ $key }}</code>
                    @if($row)
                        <span class="ui-badge ui-badge-warn">{{ __('admin.app_texts.changed') }}</span>
                    @endif
                    @foreach($text['placeholders'] as $placeholder)
                        <code class="rounded bg-maqam-soft px-1 text-[11px]" dir="ltr">{{ '{'.$placeholder.'}' }}</code>
                    @endforeach
                </div>
                @if($text['plural'])
                    <p class="text-sm">{{ $text['ar'] }}</p>
                    <p class="ui-muted text-xs">{{ __('admin.app_texts.plural_note') }}</p>
                @else
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach(['ar' => 'العربية', 'en' => 'English'] as $locale => $label)
                            <div>
                                <label class="ui-muted mb-1 block text-xs">{{ $label }}</label>
                                <textarea name="texts[{{ $key }}][{{ $locale }}]" rows="{{ mb_strlen($text[$locale]) > 70 ? 3 : 1 }}"
                                          dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" placeholder="{{ $text[$locale] }}"
                                          class="ui-textarea">{{ old("texts.$key.$locale", $row?->{'value_'.$locale}) }}</textarea>
                                @error("texts.$key.$locale")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="ui-empty">{{ __('admin.empty_title') }}</div>
        @endforelse

        @if($texts->isNotEmpty())
            <div class="sticky bottom-4 flex justify-end">
                <button class="ui-btn ui-btn-primary shadow-lg">{{ __('admin.app_texts.save') }}</button>
            </div>
        @endif
    </form>
</div>
@endsection
