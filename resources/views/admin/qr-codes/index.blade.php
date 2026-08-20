@extends('admin.layouts.app')

@section('title', __('admin.qr.batches_title'))
@section('subtitle', __('admin.qr.batches_subtitle'))

@section('actions')
<div class="flex gap-2">
    <form action="{{ route('admin.qr-codes.restore') }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
        @csrf
        <input type="file" name="json_file" accept=".json" class="ui-input max-w-xs" id="restore-file">
        <button type="submit" class="ui-btn ui-btn-dark">{{ __('admin.qr.restore') }}</button>
    </form>
    <a href="{{ route('admin.qr-codes.tracker') }}" class="ui-btn ui-btn-ghost">{{ __('admin.nav.qr_tracker') }}</a>
    <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.nav.generate_batch') }}</a>
</div>
@endsection

@section('content')
<form class="ui-toolbar">
    <input name="batch_id" value="{{ request('batch_id') }}" placeholder="{{ __('admin.qr.batch_id') }}" class="ui-input max-w-xs" dir="ltr">
    <select name="category_id" class="ui-select max-w-xs" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }} — {{ __('admin.nav.prize_categories') }}</option>
        @foreach($prizeCategories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name_ar }}</option>
        @endforeach
    </select>
    <select name="status" class="ui-select max-w-[10rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        <option value="active" @selected(request('status')==='active')>{{ __('admin.active') }}</option>
        <option value="used" @selected(request('status')==='used')>مستخدم</option>
        <option value="expired" @selected(request('status')==='expired')>منتهي</option>
    </select>
    <button class="ui-btn ui-btn-dark">{{ __('admin.filter') }}</button>
</form>

@if($batches->isEmpty() && !request()->hasAny(['batch_id','status','category_id']))
    <div class="mb-8 ui-empty">
        <h3 class="font-semibold">{{ __('admin.qr.empty_batches') }}</h3>
        <a href="{{ route('admin.qr-codes.create') }}" class="ui-btn ui-btn-primary mt-4">{{ __('admin.nav.generate_batch') }}</a>
    </div>
@else
    <div class="mb-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($batches as $batch)
            @php $cat = $categories[$batch->category_id] ?? null; @endphp
            <div class="ui-card p-5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-mono text-xs ui-muted" dir="ltr">{{ $batch->batch_id }}</div>
                        <div class="mt-1 font-semibold">{{ $cat?->name_ar ?? '—' }}</div>
                    </div>
                    <span class="ui-badge ui-badge-muted inline-flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-full" style="background:{{ $cat->background_color ?? '#C5A059' }}"></span>
                        {{ $cat->background_color ?? '' }}
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] py-2"><div class="ui-muted">{{ __('admin.qr.count') }}</div><div class="font-semibold">{{ $batch->total }}</div></div>
                    <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] py-2"><div class="ui-muted">{{ __('admin.active') }}</div><div class="font-semibold text-emerald-700">{{ $batch->active_count }}</div></div>
                    <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] py-2"><div class="ui-muted">مستخدم</div><div class="font-semibold">{{ $batch->used_count }}</div></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2 ui-muted">
                    <span>{{ __('admin.qr.created_at') }}: {{ \Illuminate\Support\Carbon::parse($batch->generated_at)->diffForHumans() }}</span>
                    @php
                        $statusLabel = match($batch->build_status) {
                            'queued', 'processing' => __('admin.qr.status_building'),
                            'ready' => __('admin.qr.status_ready'),
                            'failed' => __('admin.qr.status_failed'),
                            default => __('admin.qr.zip_missing'),
                        };
                        $statusClass = match($batch->build_status) {
                            'queued', 'processing' => 'ui-badge-warn',
                            'ready' => 'ui-badge-ok',
                            default => 'ui-badge-muted',
                        };
                    @endphp
                    <span class="ui-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($batch->zip_exists)
                        <a href="{{ route('admin.qr-codes.download', $batch->batch_id) }}" class="ui-btn ui-btn-primary flex-1">{{ __('admin.download') }} ZIP</a>
                    @else
                        <form method="POST" action="{{ route('admin.qr-codes.rebuild', $batch->batch_id) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="ui-btn ui-btn-primary w-full" @disabled(in_array($batch->build_status, ['queued','processing'], true))>
                                {{ in_array($batch->build_status, ['queued','processing'], true) ? __('admin.qr.status_building') : __('admin.qr.rebuild_zip') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.qr-codes.download-json', $batch->batch_id) }}" class="ui-btn ui-btn-ghost flex-1">JSON</a>
                    <a href="{{ route('admin.qr-codes.index', ['batch_id' => $batch->batch_id]) }}" class="ui-btn ui-btn-ghost flex-1">{{ __('admin.qr.open_codes') }}</a>
                </div>
                <form method="POST" action="{{ route('admin.qr-codes.destroy-batch', $batch->batch_id) }}" class="mt-2"
                      onsubmit="return confirmBatchDelete(this, {{ (int) $batch->used_count }})">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="force" value="0">
                    <button type="submit" class="ui-btn ui-btn-ghost w-full text-xs text-red-700">{{ __('admin.qr.delete_batch') }}</button>
                </form>
            </div>
        @endforeach
    </div>
@endif

<div class="ui-table-wrap">
    <div class="border-b border-[#D8D4CB] px-5 py-4 font-semibold">{{ __('admin.qr.serial') }}</div>
    <div class="overflow-x-auto">
        <table class="ui-table">
            <thead>
            <tr>
                <th>{{ __('admin.qr.serial') }}</th>
                <th>{{ __('admin.nav.prize_categories') }}</th>
                <th>{{ __('admin.qr.points') }}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.qr.batch_id') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($codes as $code)
                <tr>
                    <td class="font-mono text-xs" dir="ltr">{{ $code->serial_code }}</td>
                    <td>
                        <span class="inline-flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background:{{ $code->categoryPrize?->background_color }}"></span>
                            {{ $code->categoryPrize?->name_ar }}
                        </span>
                    </td>
                    <td class="font-semibold text-maqam-gold-dark">{{ $code->points_awarded }}</td>
                    <td>
                        <span class="ui-badge {{ $code->status==='active'?'ui-badge-ok':($code->status==='used'?'ui-badge-muted':'ui-badge-warn') }}">{{ $code->status }}</span>
                    </td>
                    <td class="font-mono text-[11px] ui-muted" dir="ltr">{{ $code->batch_id }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-8 text-center ui-muted">{{ __('admin.empty_title') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $codes->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
function confirmBatchDelete(form, usedCount) {
    const forceInput = form.querySelector('input[name="force"]');
    if (usedCount > 0) {
        const ok = confirm(@json(__('admin.qr.delete_batch_force_confirm')));
        if (!ok) return false;
        forceInput.value = '1';
        return true;
    }
    return confirm(@json(__('admin.qr.delete_batch_confirm')));
}
</script>
@endpush
