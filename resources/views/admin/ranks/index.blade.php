@extends('admin.layouts.app')

@section('title', __('admin.ranks.title'))
@section('subtitle', __('admin.ranks.subtitle'))
@section('actions')
<a href="{{ route('admin.ranks.create') }}" class="ui-btn ui-btn-primary">{{ __('admin.ranks.add') }}</a>
@endsection

@section('content')
<div class="mb-8 ui-panel">
    <h3 class="mb-2 ui-section-title">{{ __('admin.ranks.simulate') }}</h3>
    <p class="mb-3 ui-muted">{{ __('admin.ranks.simulate_hint') }}</p>
    <div class="flex flex-wrap gap-2">
        <input type="number" id="simPts" min="0" value="500" class="ui-input max-w-[10rem]" oninput="simulate()">
        <div class="ui-chip">{{ __('admin.ranks.result') }}: <strong id="simResult">—</strong></div>
    </div>
</div>

<div class="grid gap-5 md:grid-cols-3">
    @forelse($ranks as $rank)
        @php
            $tone = match(true) {
                str_contains(strtolower($rank->name_en), 'plat') => 'from-[#c0c0c0] to-[#e8e8e8]',
                str_contains(strtolower($rank->name_en), 'gold') => 'from-maqam-gold-dark to-maqam-gold-light',
                default => 'from-slate-400 to-slate-200',
            };
        @endphp
        <div class="ui-card overflow-hidden">
            <div class="h-2 bg-gradient-to-l {{ $tone }}"></div>
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xl font-semibold">{{ $rank->name_ar }}</div>
                        <div class="ui-muted">{{ $rank->name_en }}</div>
                    </div>
                    <span class="ui-badge {{ $rank->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">{{ $rank->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="ui-muted">{{ __('admin.ranks.min_points') }}</span><strong>{{ number_format($rank->min_points) }}</strong></div>
                    <div class="flex justify-between"><span class="ui-muted">{{ __('admin.ranks.max_points') }}</span><strong>{{ $rank->max_points !== null ? number_format($rank->max_points) : '∞' }}</strong></div>
                    <div class="flex justify-between"><span class="ui-muted">{{ __('admin.ranks.merchant_pts') }}</span><strong>{{ $rank->merchant_points_per_scan }}</strong></div>
                    <div class="flex justify-between"><span class="ui-muted">{{ __('admin.ranks.wheel_cost') }}</span><strong>{{ $rank->wheel_cost_points }}</strong></div>
                    <div class="flex justify-between"><span class="ui-muted">{{ __('admin.ranks.wheel_prob') }}</span><strong>{{ round($rank->wheel_win_probability * 100) }}%</strong></div>
                </div>
                <a href="{{ route('admin.ranks.edit', $rank) }}" class="ui-btn ui-btn-ghost ui-btn-block mt-5">{{ __('admin.edit') }}</a>
            </div>
        </div>
    @empty
        <div class="ui-empty col-span-full">{{ __('admin.empty_title') }}</div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
const ranks = @json($ranks->map(fn($r)=>['ar'=>$r->name_ar,'min'=>$r->min_points,'max'=>$r->max_points]));
function simulate(){
    const pts = +document.getElementById('simPts').value || 0;
    const hit = ranks.find(r => pts >= r.min && (r.max === null || pts <= r.max));
    document.getElementById('simResult').textContent = hit ? hit.ar : '—';
}
simulate();
</script>
@endpush
