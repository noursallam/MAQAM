@extends('admin.layouts.app')

@section('title', __('admin.qr.prize_studio'))
@section('subtitle', __('admin.qr.prize_subtitle'))
@section('breadcrumbs')
<a href="{{ route('admin.dashboard') }}" class="hover:text-maqam-ink">{{ __('admin.nav.command_center') }}</a>
<span class="mx-1">/</span>
<span>{{ __('admin.nav.loyalty') }}</span>
@endsection

@section('actions')
<a href="{{ route('admin.prize-categories.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.qr.add_category') }}</a>
@endsection

@section('content')
@if($categories->isEmpty())
    <div class="ui-empty">
        <h3 class="text-lg font-semibold">{{ __('admin.empty_title') }}</h3>
        <p class="ui-muted mt-2">{{ __('admin.empty_hint') }}</p>
        <a href="{{ route('admin.prize-categories.create') }}" class="ui-btn ui-btn-primary mt-6">{{ __('admin.qr.add_category') }}</a>
    </div>
@else
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($categories as $category)
            <div class="ui-card overflow-hidden">
                <div class="relative flex h-36 items-center justify-center border-b border-[#D8D4CB]" style="background: {{ $category->background_color }}">
                    @if($category->hasImage())
                        <img src="{{ $category->image_url }}" alt="{{ $category->name_ar }}" class="h-full w-full object-cover">
                    @else
                        <div class="rounded-lg border border-[#D8D4CB] bg-white p-5">
                            <div class="grid grid-cols-5 gap-0.5">
                                @for($i=0;$i<25;$i++)
                                    <span class="h-1.5 w-1.5 rounded-[1px] {{ $i % 3 === 0 ? 'bg-maqam-ink' : 'bg-maqam-ink/20' }}"></span>
                                @endfor
                            </div>
                        </div>
                    @endif
                    <span class="ui-badge absolute start-3 top-3 border-white/20 bg-black/35 text-white">{{ $category->background_color }}</span>
                </div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold">{{ $category->name_ar }}</div>
                            <div class="ui-muted mt-0.5">{{ $category->name_en }}</div>
                        </div>
                        <span class="ui-badge {{ $category->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                            {{ $category->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                    </div>
                    <div class="ui-divider mt-4 flex items-center justify-between pt-3 text-sm">
                        <div>
                            <span class="ui-muted">{{ __('admin.qr.points') }}:</span>
                            <span class="font-bold text-maqam-gold-dark">{{ $category->points_value }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-6 w-6 rounded border border-[#D8D4CB]" style="background:{{ $category->background_color }}"></span>
                            <span class="ui-muted">{{ __('admin.qr.print_color') }}</span>
                        </div>
                    </div>
                    <p class="ui-muted mt-3 leading-relaxed">{{ __('admin.qr.color_hint') }}</p>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.prize-categories.edit', $category) }}" class="ui-btn ui-btn-ghost flex-1">{{ __('admin.edit') }}</a>
                        <a href="{{ route('admin.qr-codes.create', ['category_id' => $category->id]) }}" class="ui-btn ui-btn-dark flex-1">{{ __('admin.nav.generate_batch') }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $categories->links() }}</div>
@endif
@endsection
