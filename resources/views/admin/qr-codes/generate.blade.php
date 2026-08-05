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
    $processing = request('processing');
    $preselect = (int) request('category_id', old('category_id'));
    $batchId = request('batch');
@endphp

@if($processing && $batchId)
    <div class="ui-card-static mx-auto max-w-xl p-8 text-center" id="batchProgressCard"
         data-status-url="{{ route('admin.qr-codes.status', $batchId) }}"
         data-download-url="{{ route('admin.qr-codes.download', $batchId) }}">
        <div id="progressIcon" class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-2xl text-amber-700">⏳</div>
        <h2 id="progressTitle" class="text-xl font-semibold">{{ __('admin.qr.queued_title') }}</h2>
        <p id="progressHint" class="ui-muted mt-2">{{ __('admin.qr.queued_hint') }}</p>
        <div class="mt-6 grid gap-3 text-sm">
            <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.batch_id') }}</span><code dir="ltr">{{ $batchId }}</code></div>
            <div class="ui-row"><span class="ui-muted">{{ __('admin.qr.count') }}</span><strong id="progressCount">{{ request('count') }}</strong></div>
            @if(request('color'))
            <div class="ui-row">
                <span class="ui-muted">{{ __('admin.qr.color_used') }}</span>
                <span class="flex items-center gap-2"><span class="h-6 w-6 rounded border border-maqam-line" style="background:{{ request('color') }}"></span><code dir="ltr">{{ request('color') }}</code></span>
            </div>
            @endif
        </div>
        <div class="mt-6">
            <div class="mb-2 flex justify-between text-xs ui-muted">
                <span id="progressLabel">0%</span>
                <span id="progressDetail">0 / {{ request('count') ?: '—' }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-[#E8E4DB]">
                <div id="progressBar" class="h-full rounded-full bg-maqam-gold transition-all duration-500" style="width:0%"></div>
            </div>
        </div>
        <p id="progressError" class="mt-4 hidden text-sm text-red-700"></p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a id="downloadBtn" href="{{ route('admin.qr-codes.download', $batchId) }}" class="ui-btn ui-btn-primary pointer-events-none opacity-40">{{ __('admin.qr.download_zip') }}</a>
            <a href="{{ route('admin.qr-codes.index', ['batch_id' => $batchId]) }}" class="ui-btn ui-btn-dark">{{ __('admin.qr.view_batch') }}</a>
            <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-ghost">{{ __('admin.qr.generate_another') }}</a>
        </div>
    </div>
@elseif($done)
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
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">{{ __('admin.qr.async_note') }}</p>
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

@if($processing && $batchId)
(function pollBatch(){
    const card = document.getElementById('batchProgressCard');
    if(!card) return;
    const url = card.dataset.statusUrl;
    const titles = {
        queued: @json(__('admin.qr.queued_title')),
        processing: @json(__('admin.qr.processing_title')),
        ready: @json(__('admin.qr.success_title')),
        failed: @json(__('admin.qr.failed_title')),
    };
    const hints = {
        queued: @json(__('admin.qr.queued_hint')),
        processing: @json(__('admin.qr.processing_hint')),
        ready: @json(__('admin.qr.ready_hint')),
        failed: @json(__('admin.qr.failed_hint')),
    };

    async function tick(){
        try {
            const res = await fetch(url + (url.includes('?') ? '&' : '?') + 'work=1', {
                headers: {'Accept': 'application/json'},
                credentials: 'same-origin',
            });
            if(!res.ok) throw new Error('status '+res.status);
            const data = await res.json();
            const pct = data.progress ?? 0;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressLabel').textContent = pct + '%';
            document.getElementById('progressDetail').textContent = (data.processed_count ?? 0) + ' / ' + (data.quantity ?? '—');
            document.getElementById('progressCount').textContent = data.quantity ?? '—';
            document.getElementById('progressTitle').textContent = titles[data.status] || titles.processing;
            document.getElementById('progressHint').textContent = hints[data.status] || hints.processing;

            if(data.zip_ready || data.status === 'ready'){
                document.getElementById('progressIcon').textContent = '✓';
                document.getElementById('progressIcon').className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700';
                const btn = document.getElementById('downloadBtn');
                btn.classList.remove('pointer-events-none','opacity-40');
                if(data.download_url) btn.href = data.download_url;
                return;
            }

            if(data.status === 'failed'){
                document.getElementById('progressIcon').textContent = '!';
                document.getElementById('progressIcon').className = 'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-2xl text-red-700';
                const err = document.getElementById('progressError');
                err.textContent = data.error_message || hints.failed;
                err.classList.remove('hidden');
                return;
            }

            // Continue immediately — each request builds ~40 codes in-app
            setTimeout(tick, 250);
        } catch (e) {
            setTimeout(tick, 2000);
        }
    }
    tick();
})();
@endif
</script>
@endpush
