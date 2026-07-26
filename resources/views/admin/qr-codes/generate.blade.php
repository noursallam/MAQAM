@extends('admin.layouts.app')

@section('title', __('admin.qr.generate_wizard'))
@section('subtitle', __('admin.qr.wizard_subtitle'))
@section('breadcrumbs')
<a href="{{ route('admin.prize-categories.index') }}" class="hover:text-maqam-ink">{{ __('admin.nav.prize_categories') }}</a>
<span class="mx-1">/</span>
<span>{{ __('admin.nav.generate_batch') }}</span>
@endsection

@section('content')
@php
    $done = request('done');
    $preselect = (int) request('category_id', old('category_id'));
@endphp

@if($done)
    <div class="ui-card-static mx-auto max-w-xl p-8 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">✓</div>
        <h2 class="text-xl font-semibold">{{ __('admin.qr.success_title') }}</h2>
        <div class="mt-6 grid gap-3 text-sm">
            <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.batch_id') }}</span><code dir="ltr">{{ request('batch') }}</code></div>
            <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.count') }}</span><strong>{{ request('count') }}</strong></div>
            <div class="ui-row">
                <span class="ui-muted">{{ __('admin.qr.color_used') }}</span>
                <span class="flex items-center gap-2"><span class="h-6 w-6 rounded border border-maqam-line" style="background:{{ request('color') }}"></span><code dir="ltr">{{ request('color') }}</code></span>
            </div>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('admin.qr-codes.download', request('batch')) }}" class="ui-btn ui-btn-primary">{{ __('admin.qr.download_zip') }}</a>
            <a href="{{ route('admin.qr-codes.index', ['batch_id' => request('batch')]) }}" class="ui-btn ui-btn-dark">{{ __('admin.qr.view_batch') }}</a>
            <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.qr.generate_another') }}</a>
        </div>
    </div>
@else
<form id="wizardForm" method="POST" action="{{ route('admin.qr-codes.store') }}" class="mx-auto max-w-3xl">
    @csrf
    <input type="hidden" name="category_id" id="category_id" value="{{ $preselect ?: '' }}">
    <input type="hidden" name="quantity" id="quantity_input" value="{{ old('quantity', 50) }}">
    <input type="hidden" name="notes" id="notes_input" value="">

    {{-- Steps --}}
    <div class="mb-8 flex items-center justify-between gap-2">
        @foreach([1 => __('admin.qr.step1'), 2 => __('admin.qr.step2'), 3 => __('admin.qr.step3')] as $n => $label)
            <div class="flex flex-1 flex-col items-center gap-2">
                <div id="stepDot{{ $n }}" class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold {{ $n===1 ? 'bg-maqam-gold text-maqam-navy' : 'bg-white text-maqam-muted border border-maqam-line' }}">{{ $n }}</div>
                <span class="ui-muted hidden text-center sm:block">{{ $label }}</span>
            </div>
            @if($n < 3)<div class="mb-5 h-px flex-1 bg-maqam-line"></div>@endif
        @endforeach
    </div>

    {{-- Step 1 --}}
    <div id="step1" class="ui-card-static p-6">
        <h2 class="ui-section-title mb-4">{{ __('admin.qr.step1') }}</h2>
        @if($prizeCategories->isEmpty())
            <p class="ui-muted">{{ __('admin.empty_title') }}</p>
            <a href="{{ route('admin.prize-categories.create') }}" class="mt-4 inline-block text-sm text-maqam-gold-dark underline">{{ __('admin.qr.add_category') }}</a>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($prizeCategories as $cat)
                    <button type="button" onclick="selectCategory({{ $cat->id }}, '{{ $cat->background_color }}', {{ $cat->points_value }}, '{{ addslashes($cat->name_ar) }}')"
                            class="cat-card ui-card p-4 text-start {{ $preselect === $cat->id ? 'border-maqam-gold' : '' }}"
                            data-id="{{ $cat->id }}">
                        <div class="mb-3 flex h-20 items-center justify-center rounded" style="background:{{ $cat->background_color }}">
                            <div class="rounded bg-white px-4 py-3 text-[10px]">QR</div>
                        </div>
                        <div class="font-medium">{{ $cat->name_ar }}</div>
                        <div class="ui-muted mt-1 flex justify-between">
                            <span>{{ $cat->points_value }} {{ __('admin.qr.points') }}</span>
                            <code dir="ltr">{{ $cat->background_color }}</code>
                        </div>
                    </button>
                @endforeach
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="goStep(2)" class="ui-btn ui-btn-primary">{{ __('admin.qr.next') }}</button>
            </div>
        @endif
    </div>

    {{-- Step 2 --}}
    <div id="step2" class="ui-card-static hidden p-6">
        <h2 class="ui-section-title mb-4">{{ __('admin.qr.step2') }}</h2>
        <div class="ui-card-soft mb-4 p-4 text-sm">
            <span id="s2_name">—</span> · <strong id="s2_pts">—</strong> {{ __('admin.qr.points') }}
            · <span class="inline-flex items-center gap-2"><span id="s2_swatch" class="h-5 w-5 rounded border border-maqam-line"></span><code id="s2_color" dir="ltr"></code></span>
        </div>
        <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.quantity') }} ({{ __('admin.qr.max') }} {{ $maxBatch }})</label>
        <input type="number" id="qty_ui" min="1" max="{{ $maxBatch }}" value="{{ old('quantity', 50) }}" class="ui-input"
               oninput="document.getElementById('quantity_input').value=this.value; document.getElementById('s3_qty').textContent=this.value">
        <label class="mb-1 mt-4 block text-sm font-medium">{{ __('admin.qr.batch_notes') }}</label>
        <textarea id="notes_ui" rows="2" class="ui-textarea" oninput="document.getElementById('notes_input').value=this.value"></textarea>
        <div class="mt-6 flex justify-between">
            <button type="button" onclick="goStep(1)" class="ui-btn ui-btn-ghost">{{ __('admin.qr.prev') }}</button>
            <button type="button" onclick="goStep(3)" class="ui-btn ui-btn-primary">{{ __('admin.qr.next') }}</button>
        </div>
    </div>

    {{-- Step 3 --}}
    <div id="step3" class="ui-card-static hidden p-6">
        <h2 class="ui-section-title mb-4">{{ __('admin.qr.step3') }}</h2>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <div id="s3_preview" class="flex aspect-square items-center justify-center" style="border-radius: 1rem">
                    <div class="ui-card-static p-8">
                        <div class="mx-auto grid w-28 grid-cols-7 gap-0.5">
                            @for($i=0;$i<49;$i++)
                                <span class="h-2.5 w-2.5 rounded-[1px] bg-maqam-ink {{ $i%3===0?'opacity-100':'opacity-25' }}"></span>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-3 text-sm">
                <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.step1') }}</span><strong id="s3_name">—</strong></div>
                <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.quantity') }}</span><strong id="s3_qty">50</strong></div>
                <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.points') }}</span><strong id="s3_pts">—</strong></div>
                <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.print_color') }}</span><span class="flex items-center gap-2"><span id="s3_swatch" class="h-6 w-6 rounded border border-maqam-line"></span><code id="s3_color" dir="ltr"></code></span></div>
                <p class="ui-muted">{{ __('admin.qr.color_hint') }}</p>
            </div>
        </div>
        <div class="mt-6 flex justify-between">
            <button type="button" onclick="goStep(2)" class="ui-btn ui-btn-ghost">{{ __('admin.qr.prev') }}</button>
            <button type="submit" id="genBtn" onclick="this.disabled=true; this.textContent=@json(__('admin.qr.generating')); this.form.submit();"
                    class="ui-btn ui-btn-primary">{{ __('admin.qr.confirm_generate') }}</button>
        </div>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
let selected = {id: {{ $preselect ?: 'null' }}, color:'#C5A059', pts:0, name:''};
function selectCategory(id, color, pts, name){
    selected = {id, color, pts, name};
    document.getElementById('category_id').value = id;
    document.querySelectorAll('.cat-card').forEach(el => {
        el.classList.toggle('border-maqam-gold', +el.dataset.id === id);
    });
    syncPreview();
}
function syncPreview(){
    ['s2','s3'].forEach(p => {
        const n = document.getElementById(p+'_name'); if(n) n.textContent = selected.name || '—';
        const pts = document.getElementById(p+'_pts'); if(pts) pts.textContent = selected.pts || '—';
        const c = document.getElementById(p+'_color'); if(c) c.textContent = selected.color;
        const s = document.getElementById(p+'_swatch'); if(s) s.style.background = selected.color;
    });
    const prev = document.getElementById('s3_preview'); if(prev) prev.style.background = selected.color;
}
function goStep(n){
    if(n>1 && !document.getElementById('category_id').value){ alert(@json(__('admin.qr.step1'))); return; }
    [1,2,3].forEach(i => {
        document.getElementById('step'+i).classList.toggle('hidden', i!==n);
        const dot = document.getElementById('stepDot'+i);
        const active = i===n || i<n;
        dot.className = 'flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold ' + (active ? 'bg-maqam-gold text-maqam-navy' : 'bg-white text-maqam-muted border border-maqam-line');
    });
    syncPreview();
}
@if($preselect)
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.cat-card[data-id="{{ $preselect }}"]');
    if(btn) btn.click();
});
@endif
</script>
@endpush
