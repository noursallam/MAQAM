{{-- TEMPORARY / EXPERIMENTAL (تجريبي مؤقت): quick lucky-wheel page. See Store\SpinController. --}}
@extends('store.layouts.app')

@php $ar = app()->getLocale() === 'ar'; @endphp

@section('title', ($ar ? 'عجلة الحظ' : 'Lucky wheel').' | MAQAM')

@section('content')
<style>
    .mq-spin { max-width: 560px; margin: 0 auto; text-align: center; }
    .mq-spin-beta { display: inline-block; margin-bottom: .75rem; padding: .2rem .7rem; border-radius: 999px; font-size: .75rem; font-weight: 600; background: rgba(245, 158, 11, .15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, .35); }
    .mq-spin-stats { display: flex; justify-content: center; gap: 1rem; margin: 1rem 0 1.5rem; flex-wrap: wrap; }
    .mq-spin-stat { padding: .6rem 1rem; border: 1px solid var(--mq-line); border-radius: 12px; background: var(--mq-surface); min-width: 9rem; }
    .mq-spin-stat span { display: block; font-size: .78rem; color: var(--mq-muted); }
    .mq-spin-stat strong { font-size: 1.25rem; color: var(--mq-gold); }
    .mq-spin-wheel-wrap { position: relative; width: min(360px, 88vw); aspect-ratio: 1; margin: 0 auto; }
    .mq-spin-pointer { position: absolute; top: -6px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 14px solid transparent; border-right: 14px solid transparent; border-top: 26px solid var(--mq-gold); z-index: 2; filter: drop-shadow(0 2px 3px rgba(0,0,0,.4)); }
    .mq-spin-wheel { width: 100%; height: 100%; transition: transform 5s cubic-bezier(.17, .67, .12, .99); }
    .mq-spin-btn { margin-top: 1.5rem; min-width: 12rem; justify-content: center; }
    .mq-spin-btn:disabled { opacity: .5; cursor: not-allowed; }
    .mq-spin-result { min-height: 1.6rem; margin-top: 1rem; font-weight: 600; }
    .mq-spin-result.is-win { color: #22c55e; }
    .mq-spin-result.is-error { color: #f87171; }
</style>

<section class="mq-page">
    <div class="mq-container mq-spin">
        <span class="mq-spin-beta">{{ $ar ? 'نسخة تجريبية' : 'Beta' }}</span>
        <h1 class="mq-page-title">{{ $ar ? 'عجلة الحظ' : 'Lucky wheel' }}</h1>

        @if(! $enabled)
            <p class="mq-page-lead">{{ __('admin.wheel.disabled') }}</p>
        @elseif($isStaff)
            <p class="mq-page-lead">{{ __('store.spin.customers_only') }}</p>
        @elseif(! $hasCustomer || ! $hasRank)
            <p class="mq-page-lead">{{ __('admin.wheel.no_rank') }}</p>
        @else
            <div class="mq-spin-stats">
                <div class="mq-spin-stat"><span>{{ $ar ? 'رصيد نقاطك' : 'Your points' }}</span><strong id="mqSpinBalance">{{ number_format($balance) }}</strong></div>
                <div class="mq-spin-stat"><span>{{ $ar ? 'تكلفة اللفة' : 'Cost per spin' }}</span><strong>{{ number_format($cost) }}</strong></div>
            </div>

            <div class="mq-spin-wheel-wrap">
                <div class="mq-spin-pointer" aria-hidden="true"></div>
                <svg class="mq-spin-wheel" id="mqSpinWheel" viewBox="-100 -100 200 200" role="img" aria-label="{{ $ar ? 'عجلة الحظ' : 'Lucky wheel' }}">
                    @php
                        $n = count($slices);
                        $step = 360 / $n;
                        $colors = ['#1B2A4A', '#C5A059', '#24375f', '#a98e48'];
                    @endphp
                    @foreach($slices as $i => $slice)
                        @php
                            // Slice i is centred at the top when the wheel is at 0deg
                            $a0 = deg2rad($i * $step - $step / 2 - 90);
                            $a1 = deg2rad($i * $step + $step / 2 - 90);
                            $large = $step > 180 ? 1 : 0;
                            $mid = $i * $step;
                            $fill = $slice['id'] === null ? '#3f3f46' : $colors[$i % count($colors)];
                            $textFill = in_array($fill, ['#C5A059', '#a98e48'], true) ? '#0b0f19' : '#f5f3ee';
                        @endphp
                        @if($n === 1)
                            <circle r="96" fill="{{ $fill }}"/>
                        @else
                            <path d="M0 0 L{{ 96 * cos($a0) }} {{ 96 * sin($a0) }} A96 96 0 {{ $large }} 1 {{ 96 * cos($a1) }} {{ 96 * sin($a1) }} Z" fill="{{ $fill }}" stroke="#0b0f19" stroke-width="1"/>
                        @endif
                        <g transform="rotate({{ $mid }})">
                            <text x="0" y="-58" text-anchor="middle" dominant-baseline="middle" fill="{{ $textFill }}" font-size="{{ $n > 8 ? 7 : 9 }}" font-weight="600" font-family="IBM Plex Sans Arabic, sans-serif">{{ \Illuminate\Support\Str::limit($slice['label'], 16) }}</text>
                        </g>
                    @endforeach
                    <circle r="12" fill="#0b0f19" stroke="#C5A059" stroke-width="3"/>
                </svg>
            </div>

            <button type="button" class="mq-btn mq-btn-primary mq-spin-btn" id="mqSpinBtn" @disabled($balance < $cost)>
                {{ $ar ? 'لف العجلة' : 'Spin' }}
            </button>
            <div class="mq-spin-result" id="mqSpinResult" role="status" aria-live="polite">
                @if($balance < $cost) {{ __('admin.wheel.insufficient_points') }} @endif
            </div>
        @endif
    </div>
</section>

@if($enabled && $hasCustomer)
@php
    $spinText = [
        'won' => $ar ? 'مبروك! ربحت: ' : 'You won: ',
        'lost' => $ar ? 'حظًا أوفر في المرة القادمة' : 'No luck this time — try again',
        'error' => $ar ? 'حدثت مشكلة، حاول مرة أخرى' : 'Something went wrong, try again',
    ];
@endphp
<script>
(function () {
    var btn = document.getElementById('mqSpinBtn');
    var wheel = document.getElementById('mqSpinWheel');
    var result = document.getElementById('mqSpinResult');
    var balance = document.getElementById('mqSpinBalance');
    var count = {{ count($slices) }};
    var cost = {{ $cost }};
    var step = 360 / count;
    var rotation = 0;
    var t = @json($spinText);

    btn.addEventListener('click', function () {
        btn.disabled = true;
        result.className = 'mq-spin-result';
        result.textContent = '';

        fetch(@json(route('store.spin.play')), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
            if (!res.ok) throw new Error(res.d.message || t.error);
            var d = res.d;
            // Land the chosen slice under the pointer after a few full turns, a little off-centre
            var jitter = (Math.random() - 0.5) * step * 0.6;
            var target = 360 - (d.slice_index * step) + jitter;
            rotation += 360 * 6 + ((target - rotation % 360) + 360) % 360;
            wheel.style.transform = 'rotate(' + rotation + 'deg)';

            setTimeout(function () {
                balance.textContent = Number(d.balance).toLocaleString();
                result.className = 'mq-spin-result' + (d.is_win ? ' is-win' : '');
                result.textContent = d.is_win ? t.won + d.prize_label : t.lost;
                btn.disabled = d.balance < cost;
            }, 5100);
        })
        .catch(function (e) {
            result.className = 'mq-spin-result is-error';
            result.textContent = e.message || t.error;
            btn.disabled = false;
        });
    });
})();
</script>
@endif
@endsection
