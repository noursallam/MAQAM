@extends('admin.layouts.app')

@section('title', __('admin.import.title'))
@section('subtitle', __('admin.import.subtitle'))

@section('actions')
<a href="{{ route('admin.products.index') }}" class="ui-btn ui-btn-ghost">← {{ __('admin.nav.products') }}</a>
<a href="{{ route('admin.products.import.template') }}" class="ui-btn ui-btn-dark">{{ __('admin.import.download_template') }}</a>
@endsection

@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if(session('import_errors'))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <ul class="list-disc ps-5">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ $errors->first() }}
    </div>
@endif

@php
    $letters = range('A', 'Z');
@endphp

{{-- Visual Excel preview --}}
<div class="mb-5 overflow-hidden rounded-2xl border border-[#C5C5C5] bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#A9D08E] bg-[#217346] px-4 py-3 text-white">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm1 7V3.5L19.5 9H15zM8.2 17.2l1.5-2.4-1.4-2.3h1.5l.8 1.5.8-1.5h1.5l-1.4 2.3 1.5 2.4h-1.5l-.9-1.6-.9 1.6H8.2z"/>
                </svg>
            </div>
            <div>
                <div class="text-sm font-semibold">{{ __('admin.import.excel_title') }}</div>
                <div class="text-xs text-white/80">{{ __('admin.import.excel_hint') }}</div>
            </div>
        </div>
        <div class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium">maqam-products-template.csv</div>
    </div>

    <div class="border-b border-[#E5E5E5] bg-[#F3F3F3] px-4 py-2 text-xs text-[#595959]">
        {{ __('admin.import.format_note') }}
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-left text-[11px] leading-tight" dir="ltr">
            <thead>
                <tr class="bg-[#E7E6E6]">
                    <th class="sticky start-0 z-10 w-10 border border-[#D0D0D0] bg-[#E7E6E6] px-2 py-1.5 text-center font-semibold text-[#595959]"></th>
                    @foreach($columns as $i => $col)
                        <th class="border border-[#D0D0D0] px-2 py-1.5 text-center font-semibold text-[#595959]">{{ $letters[$i] ?? ($i + 1) }}</th>
                    @endforeach
                </tr>
                <tr class="bg-[#E2EFDA]">
                    <th class="sticky start-0 z-10 border border-[#D0D0D0] bg-[#E2EFDA] px-2 py-2 text-center font-bold text-[#375623]">1</th>
                    @foreach($columns as $col)
                        <th class="border border-[#D0D0D0] px-2.5 py-2 whitespace-nowrap font-bold text-[#375623]">
                            {{ $col['key'] }}
                            @if($col['required'])
                                <span class="ms-0.5 text-red-600">*</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($sampleRows as $r => $row)
                    <tr class="{{ $r % 2 === 0 ? 'bg-white' : 'bg-[#FAFAFA]' }} hover:bg-[#FFF2CC]/transition-colors">
                        <td class="sticky start-0 z-10 border border-[#D0D0D0] bg-[#E7E6E6] px-2 py-2 text-center font-semibold text-[#595959]">{{ $r + 2 }}</td>
                        @foreach($columns as $col)
                            @php $val = $row[$col['key']] ?? ''; @endphp
                            <td class="border border-[#D0D0D0] px-2.5 py-2 whitespace-nowrap {{ $val === '' ? 'text-[#B0B0B0] italic' : 'text-[#1F1F1F]' }}">
                                {{ $val === '' ? '—' : $val }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="bg-[#F8F8F8]">
                    <td class="sticky start-0 z-10 border border-[#D0D0D0] bg-[#E7E6E6] px-2 py-2 text-center font-semibold text-[#595959]">{{ count($sampleRows) + 2 }}</td>
                    @foreach($columns as $col)
                        <td class="border border-dashed border-[#D0D0D0] px-2.5 py-2 text-[#B0B0B0]">…</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 border-t border-[#E5E5E5] bg-[#FBFBFB] p-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($columns as $col)
            <div class="rounded-xl border border-[#E8E8E8] bg-white px-3 py-2.5">
                <div class="flex items-center gap-2">
                    <code class="rounded bg-[#E2EFDA] px-1.5 py-0.5 text-[11px] font-semibold text-[#375623]" dir="ltr">{{ $col['key'] }}</code>
                    @if($col['required'])
                        <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-medium text-red-700">{{ __('admin.import.required') }}</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ __('admin.import.optional') }}</span>
                    @endif
                </div>
                <p class="mt-1.5 text-xs text-maqam-muted">{{ __('admin.import.'.$col['note']) }}</p>
            </div>
        @endforeach
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-[1fr_320px]">
    <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data" class="ui-card-static space-y-4 p-6">
        @csrf
        <h2 class="ui-section-title">{{ __('admin.import.upload') }}</h2>
        <p class="ui-muted text-sm">{{ __('admin.import.help') }}</p>
        <ol class="list-decimal space-y-1 ps-5 text-sm text-maqam-muted">
            <li>{{ __('admin.import.step_1') }}</li>
            <li>{{ __('admin.import.step_2') }}</li>
            <li>{{ __('admin.import.step_3') }}</li>
        </ol>
        <input type="file" name="file" accept=".csv,text/csv" required class="ui-input">
        <button class="ui-btn ui-btn-primary">{{ __('admin.import.run') }}</button>
    </form>

    <div class="ui-card-static h-fit space-y-3 p-5">
        <h2 class="ui-section-title">{{ __('admin.import.categories_help') }}</h2>
        <p class="ui-muted text-sm">{{ __('admin.import.categories_hint') }}</p>
        @if($categories->isNotEmpty())
            <div class="max-h-72 space-y-2 overflow-y-auto pe-1">
                @foreach($categories as $cat)
                    <div class="rounded-lg border border-[#E8E4DA] bg-[#FAF8F3] px-3 py-2 text-xs">
                        <div class="font-medium text-maqam-ink">{{ $cat->name_ar ?: $cat->name_en }}</div>
                        <div class="mt-0.5 font-mono text-maqam-muted" dir="ltr">id: {{ $cat->id }} · slug: {{ $cat->slug ?: '—' }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-amber-700">{{ __('admin.import.no_category') }}</p>
        @endif
    </div>
</div>
@endsection
