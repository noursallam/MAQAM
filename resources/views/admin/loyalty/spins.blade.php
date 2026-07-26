@extends('admin.layouts.app')
@section('title', __('admin.wheel.title'))
@section('subtitle', __('admin.wheel.subtitle'))
@section('content')
@php
    $typeLabels = [
        'points' => __('admin.wheel.type_points'),
        'coupon' => __('admin.wheel.type_coupon'),
        'product' => __('admin.wheel.type_product'),
        'discount' => __('admin.wheel.type_discount'),
    ];
@endphp

{{-- Layer 1 --}}
<div class="mb-6 grid gap-4 lg:grid-cols-3">
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.status') }}</div>
        <div class="ui-kpi-value !text-xl">{{ $wheelEnabled ? __('admin.wheel.enabled') : __('admin.wheel.disabled') }}</div>
        <form method="POST" action="{{ route('admin.loyalty.wheel-toggle') }}" class="mt-4">
            @csrf
            <input type="hidden" name="enabled" value="{{ $wheelEnabled ? 0 : 1 }}">
            <button class="ui-btn {{ $wheelEnabled ? 'border border-red-200 bg-red-50 text-red-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                {{ $wheelEnabled ? __('admin.wheel.disabled') : __('admin.wheel.enabled') }}
            </button>
        </form>
    </div>
    <div class="ui-kpi">
        <div class="ui-kpi-label">{{ __('admin.wheel.win_rate') }}</div>
        <div class="ui-kpi-value text-maqam-gold-dark">{{ $winRate }}%</div>
        <div class="ui-kpi-hint">{{ $wins }} / {{ $total }}</div>
    </div>
    <div class="ui-card-static p-6">
        <div class="mb-3 ui-kpi-label">{{ __('admin.wheel.layer1_title') }}</div>
        <p class="ui-muted mb-3">{{ __('admin.wheel.layer1_hint') }}</p>
        @foreach($ranks as $rank)
            <div class="mb-2 flex justify-between text-sm">
                <span>{{ $rank->name_ar }}</span>
                <span class="font-semibold">{{ round($rank->wheel_win_probability * 100) }}% · {{ $rank->wheel_cost_points }} pts</span>
            </div>
        @endforeach
        <a href="{{ route('admin.ranks.index') }}" class="mt-3 inline-block text-xs font-semibold text-maqam-gold-dark hover:underline">{{ __('admin.wheel.edit_ranks') }}</a>
    </div>
</div>

{{-- Customer spin simulation --}}
@php
    $activePrizes = $prizes->where('is_active', true)->values();
    $wheelColors = ['#C5A059', '#161b29', '#dcb479', '#2a3144', '#a98e48', '#0B0F19', '#EAE7DF', '#5F6368'];
    $wheelSegments = $activePrizes->map(function ($p, $i) use ($wheelColors) {
        return [
            'id' => $p->id,
            'label' => $p->label_ar,
            'weight' => 1, // equal slices on chart — real odds stay server-side
            'color' => $wheelColors[$i % count($wheelColors)],
            'text' => in_array($i % count($wheelColors), [1, 5, 7], true) ? '#fff' : '#0B0F19',
        ];
    })->all();
    // Equal visual loss slice (landing marker only)
    $wheelSegments[] = [
        'id' => null,
        'label' => __('admin.wheel.is_loss'),
        'weight' => 1,
        'color' => '#ECEAE4',
        'text' => '#5F6368',
        'is_loss' => true,
    ];
@endphp

<div class="ui-card-static mb-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="ui-section-title">{{ __('admin.wheel.simulate_title') }}</h2>
            <p class="ui-muted mt-1">{{ __('admin.wheel.simulate_hint') }}</p>
        </div>
        @unless($wheelEnabled)
            <span class="ui-badge ui-badge-danger">{{ __('admin.wheel.disabled') }}</span>
        @endunless
    </div>

    <div class="mt-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[240px] flex-1">
            <label class="mb-1 block text-sm font-medium">{{ __('admin.orders.customer') }}</label>
            <select id="simCustomer" class="ui-select" @disabled(! $wheelEnabled || $customers->isEmpty())>
                <option value="">{{ __('admin.wheel.pick_customer') }}</option>
                @foreach($customers as $customer)
                    <option
                        value="{{ $customer->id }}"
                        data-balance="{{ $customer->points_balance }}"
                        data-cost="{{ $customer->rank?->wheel_cost_points ?? 50 }}"
                        data-name="{{ $customer->user?->full_name ?? '#'.$customer->id }}"
                    >
                        {{ $customer->user?->full_name ?? '#'.$customer->id }}
                        · {{ $customer->rank?->name_ar ?? '—' }}
                        · {{ number_format($customer->points_balance) }} pts
                        · تكلفة {{ $customer->rank?->wheel_cost_points ?? 50 }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="button" id="openWheelModal" class="ui-btn ui-btn-dark" @disabled(! $wheelEnabled || $customers->isEmpty())>
            {{ __('admin.wheel.spin_now') }}
        </button>
    </div>
</div>

{{-- Wheel modal --}}
<div id="wheelModal" class="wheel-modal" aria-hidden="true">
    <div class="wheel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="wheelModalTitle">
        <div class="flex items-center justify-between gap-3">
            <h3 id="wheelModalTitle" class="text-base font-semibold">{{ __('admin.wheel.simulate_title') }}</h3>
            <button type="button" id="closeWheelModal" class="ui-btn ui-btn-ghost px-3 py-1.5 text-xs">{{ __('admin.close') }}</button>
        </div>
        <p id="wheelCustomerLabel" class="ui-muted mt-1"></p>

        <div class="wheel-stage">
            <div class="wheel-pointer"></div>
            <div id="wheelDisc" class="wheel-disc"></div>
            <div class="wheel-disc-label">
                <div class="wheel-hub">MAQAM</div>
            </div>
        </div>

        <button type="button" id="spinWheelBtn" class="ui-btn ui-btn-primary ui-btn-block">{{ __('admin.wheel.spin_now') }}</button>
        <div id="wheelResult" class="wheel-result"></div>
        <button type="button" id="reloadAfterSpin" class="ui-btn ui-btn-ghost ui-btn-block mt-3 hidden">{{ __('admin.wheel.reload_history') }}</button>
    </div>
</div>

<script>
(function () {
    const segments = @json($wheelSegments);
    const simulateUrl = @json(route('admin.loyalty.wheel-simulate'));
    const csrf = @json(csrf_token());
    const i18n = {
        pick: @json(__('admin.wheel.pick_customer')),
        spinning: @json(__('admin.wheel.spinning')),
        win: @json(__('admin.wheel.is_win')),
        loss: @json(__('admin.wheel.is_loss')),
        prize: @json(__('admin.wheel.prize')),
        balance: @json(__('admin.loyalty.balance_after')),
    };

    const modal = document.getElementById('wheelModal');
    const disc = document.getElementById('wheelDisc');
    const openBtn = document.getElementById('openWheelModal');
    const closeBtn = document.getElementById('closeWheelModal');
    const spinBtn = document.getElementById('spinWheelBtn');
    const customerSelect = document.getElementById('simCustomer');
    const customerLabel = document.getElementById('wheelCustomerLabel');
    const resultEl = document.getElementById('wheelResult');
    const reloadBtn = document.getElementById('reloadAfterSpin');

    if (!modal || !disc || !segments.length) return;

    let currentRotation = 0;
    let busy = false;

    function buildWheel() {
        const total = segments.reduce((s, seg) => s + Math.max(1, seg.weight || 1), 0);
        let cursor = 0;
        const stops = [];
        const gradientParts = [];

        segments.forEach((seg) => {
            const w = Math.max(1, seg.weight || 1);
            const start = (cursor / total) * 360;
            cursor += w;
            const end = (cursor / total) * 360;
            seg._start = start;
            seg._end = end;
            seg._mid = (start + end) / 2;
            stops.push(seg);
            gradientParts.push(`${seg.color} ${start}deg ${end}deg`);
        });

        disc.style.background = `conic-gradient(from -90deg, ${gradientParts.join(', ')})`;

        // labels
        disc.querySelectorAll('.wheel-seg-label').forEach((n) => n.remove());
        stops.forEach((seg) => {
            const el = document.createElement('div');
            el.className = 'wheel-seg-label';
            el.textContent = (seg.label || '').slice(0, 12);
            const angle = seg._mid - 90;
            el.style.cssText = [
                'position:absolute',
                'left:50%',
                'top:50%',
                'width:42%',
                'text-align:center',
                'font-size:10px',
                'font-weight:700',
                `color:${seg.text || '#0B0F19'}`,
                'transform-origin:0 0',
                `transform: rotate(${angle}deg) translate(18px, -6px)`,
                'pointer-events:none',
                'white-space:nowrap',
                'overflow:hidden',
                'text-overflow:ellipsis',
            ].join(';');
            disc.appendChild(el);
        });
    }

    function openModal() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        if (!customerSelect.value) {
            alert(i18n.pick);
            return;
        }
        customerLabel.textContent = `${opt.dataset.name} · ${opt.dataset.balance} pts · −${opt.dataset.cost}`;
        resultEl.textContent = '';
        resultEl.className = 'wheel-result';
        reloadBtn.classList.add('hidden');
        spinBtn.disabled = false;
        spinBtn.textContent = @json(__('admin.wheel.spin_now'));
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        buildWheel();
    }

    function closeModal() {
        if (busy) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function targetIndex(data) {
        if (!data.win) {
            const lossIdx = segments.findIndex((s) => s.is_loss);
            return lossIdx >= 0 ? lossIdx : segments.length - 1;
        }
        const idx = segments.findIndex((s) => s.id === data.prize_id);
        return idx >= 0 ? idx : 0;
    }

    function rotationDeltaForIndex(index) {
        const seg = segments[index];
        const currentMod = ((currentRotation % 360) + 360) % 360;
        const desired = ((360 - seg._mid) % 360 + 360) % 360;
        let delta = desired - currentMod;
        if (delta <= 0) delta += 360;
        const turns = 5 + Math.floor(Math.random() * 2);
        return turns * 360 + delta;
    }

    async function spin() {
        if (busy) return;
        const customerId = customerSelect.value;
        if (!customerId) {
            alert(i18n.pick);
            return;
        }

        busy = true;
        spinBtn.disabled = true;
        spinBtn.textContent = i18n.spinning;
        resultEl.textContent = '';
        resultEl.className = 'wheel-result';

        try {
            const res = await fetch(simulateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ customer_id: Number(customerId) }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Spin failed');
            }

            const idx = targetIndex(data);
            const delta = rotationDeltaForIndex(idx);
            currentRotation += delta;
            disc.style.transform = `rotate(${currentRotation}deg)`;

            await new Promise((r) => setTimeout(r, 4300));

            if (data.win) {
                resultEl.className = 'wheel-result is-win';
                resultEl.innerHTML = `${i18n.win}: <strong>${data.prize || ''}</strong><br><span style="font-weight:500;color:#5F6368">${i18n.balance}: ${Number(data.balance).toLocaleString()} · −${data.cost} pts</span>`;
            } else {
                resultEl.className = 'wheel-result is-loss';
                resultEl.innerHTML = `${i18n.loss}<br><span style="font-weight:500">${i18n.balance}: ${Number(data.balance).toLocaleString()} · −${data.cost} pts</span>`;
            }

            // update select option balance
            const opt = customerSelect.querySelector(`option[value="${customerId}"]`);
            if (opt) {
                opt.dataset.balance = data.balance;
                const name = opt.dataset.name;
                const cost = opt.dataset.cost;
                opt.textContent = `${name} · ${data.rank || '—'} · ${Number(data.balance).toLocaleString()} pts · تكلفة ${cost}`;
                customerLabel.textContent = `${name} · ${data.balance} pts · −${cost}`;
            }

            reloadBtn.classList.remove('hidden');
            spinBtn.disabled = false;
            spinBtn.textContent = @json(__('admin.wheel.spin_again'));
        } catch (err) {
            resultEl.className = 'wheel-result is-loss';
            resultEl.textContent = err.message || 'Error';
            spinBtn.disabled = false;
            spinBtn.textContent = @json(__('admin.wheel.spin_now'));
        } finally {
            busy = false;
        }
    }

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    spinBtn?.addEventListener('click', spin);
    reloadBtn?.addEventListener('click', () => window.location.reload());
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
})();
</script>

{{-- Layer 2: prize pool --}}
<div class="mb-6 grid gap-6 xl:grid-cols-5">
    <div class="ui-card-static p-6 xl:col-span-2">
        <h2 class="ui-section-title">{{ __('admin.wheel.prizes_title') }}</h2>
        <p class="ui-muted mt-1 mb-4">{{ __('admin.wheel.prizes_hint') }}</p>

        @if($prizes->where('is_active', true)->isEmpty())
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('admin.wheel.no_prizes_warn') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.loyalty.wheel-prizes.store') }}" class="space-y-3" id="wheelPrizeForm">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_ar') }}</label>
                    <input name="label_ar" required class="ui-input" dir="rtl" value="{{ old('label_ar') }}">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.name_en') }}</label>
                    <input name="label_en" required class="ui-input" dir="ltr" value="{{ old('label_en') }}">
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.coupons.type') }}</label>
                    <select name="type" id="prizeType" class="ui-select">
                        <option value="points" @selected(old('type', 'points') === 'points')>{{ __('admin.wheel.type_points') }}</option>
                        <option value="coupon" @selected(old('type') === 'coupon')>{{ __('admin.wheel.type_coupon') }}</option>
                        <option value="product" @selected(old('type') === 'product')>{{ __('admin.wheel.type_product') }}</option>
                        <option value="discount" @selected(old('type') === 'discount')>{{ __('admin.wheel.type_discount') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.weight') }}</label>
                    <input type="number" name="weight" min="1" value="{{ old('weight', 10) }}" required class="ui-input">
                    <p class="ui-muted mt-1">{{ __('admin.wheel.weight_hint') }}</p>
                </div>
            </div>

            <div data-prize-field="points" class="prize-field">
                <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.points_amount') }}</label>
                <input type="number" name="points_amount" min="1" value="{{ old('points_amount', 100) }}" class="ui-input">
            </div>

            <div data-prize-field="coupon" class="prize-field hidden">
                <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.type_coupon') }}</label>
                <select name="coupon_id" class="ui-select">
                    <option value="">—</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" @selected(old('coupon_id') == $coupon->id)>{{ $coupon->code }} ({{ $coupon->type }} {{ $coupon->value }})</option>
                    @endforeach
                </select>
            </div>

            <div data-prize-field="product" class="prize-field hidden">
                <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.type_product') }}</label>
                <select name="product_id" class="ui-select">
                    <option value="">—</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name_ar }} · {{ $product->sku }}</option>
                    @endforeach
                </select>
            </div>

            <div data-prize-field="discount" class="prize-field hidden grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.discount_type') }}</label>
                    <select name="discount_type" class="ui-select">
                        <option value="percentage">%</option>
                        <option value="fixed">ج.م</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.discount_value') }}</label>
                    <input type="number" step="0.01" name="discount_value" value="{{ old('discount_value', 10) }}" class="ui-input">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.wheel.stock_limit') }}</label>
                <input type="number" name="stock_limit" min="1" value="{{ old('stock_limit') }}" class="ui-input" placeholder="{{ __('admin.wheel.stock_unlimited') }}">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked>
                {{ __('admin.active') }}
            </label>

            <button class="ui-btn ui-btn-primary ui-btn-block">{{ __('admin.wheel.add_prize') }}</button>
        </form>
        <script>
            (function () {
                const select = document.getElementById('prizeType');
                if (!select) return;
                const sync = () => {
                    document.querySelectorAll('#wheelPrizeForm [data-prize-field]').forEach((el) => {
                        el.classList.toggle('hidden', el.getAttribute('data-prize-field') !== select.value);
                    });
                };
                select.addEventListener('change', sync);
                sync();
            })();
        </script>    </div>

    <div class="xl:col-span-3">
        <div class="ui-table-wrap">
            <div class="flex items-center justify-between border-b border-[#C9C4B8] px-5 py-4">
                <div class="font-semibold">{{ __('admin.wheel.prize_pool') }}</div>
            </div>
            <table class="ui-table">
                <thead>
                <tr>
                    <th>{{ __('admin.wheel.prize') }}</th>
                    <th>{{ __('admin.coupons.type') }}</th>
                    <th>{{ __('admin.wheel.weight') }}</th>
                    <th>{{ __('admin.wheel.awarded') }}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($prizes as $prize)
                    @php
                        $detail = match($prize->type) {
                            'points' => $prize->points_amount.' pts',
                            'coupon' => $prize->coupon?->code ?? '—',
                            'product' => $prize->product?->name_ar ?? '—',
                            'discount' => ($prize->discount_type === 'percentage' ? $prize->discount_value.'%' : number_format($prize->discount_value, 0).' ج.م'),
                            default => '—',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="font-medium">{{ $prize->label_ar }}</div>
                            <div class="ui-muted">{{ $detail }}</div>
                        </td>
                        <td><span class="ui-badge ui-badge-gold">{{ $typeLabels[$prize->type] ?? $prize->type }}</span></td>
                        <td class="font-semibold">{{ $prize->weight }}</td>
                        <td class="ui-muted">
                            {{ $prize->awarded_count }}{{ $prize->stock_limit ? ' / '.$prize->stock_limit : '' }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.loyalty.wheel-prizes.toggle', $prize) }}">
                                @csrf
                                <button class="ui-badge {{ $prize->is_active ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                                    {{ $prize->is_active ? __('admin.active') : __('admin.inactive') }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.loyalty.wheel-prizes.destroy', $prize) }}" onsubmit="return confirm(@json(__('admin.confirm_delete')))">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-red-700 hover:underline">{{ __('admin.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center ui-muted">{{ __('admin.wheel.no_prizes') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Spin history --}}
<div class="ui-table-wrap">
    <div class="border-b border-[#C9C4B8] px-5 py-4 font-semibold">{{ __('admin.wheel.recent_spins') }}</div>
    <table class="ui-table">
        <thead>
        <tr>
            <th>{{ __('admin.orders.customer') }}</th>
            <th>{{ __('admin.nav.ranks') }}</th>
            <th>{{ __('admin.status') }}</th>
            <th>{{ __('admin.wheel.prize') }}</th>
            <th>وقت</th>
        </tr>
        </thead>
        <tbody>
        @forelse($spins as $spin)
            <tr>
                <td>{{ $spin->customer?->user?->full_name }}</td>
                <td>{{ $spin->rank?->name_ar }}</td>
                <td>
                    <span class="ui-badge {{ $spin->is_win ? 'ui-badge-ok' : 'ui-badge-muted' }}">
                        {{ $spin->is_win ? __('admin.wheel.is_win') : __('admin.wheel.is_loss') }}
                    </span>
                </td>
                <td>
                    @if($spin->is_win && $spin->prize)
                        {{ $spin->prize->label_ar }}
                        <span class="ui-muted">({{ $spin->prize_type }} {{ $spin->prize_value }})</span>
                    @else
                        {{ $spin->prize_type }} {{ $spin->prize_value }}
                    @endif
                </td>
                <td class="ui-muted">{{ $spin->spun_at?->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center ui-muted">{{ __('admin.empty_title') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4">{{ $spins->links() }}</div>
</div>
@endsection
