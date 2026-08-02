@extends('admin.layouts.app')

@section('title', __('admin.qr.scan_monitor'))
@section('subtitle', __('admin.qr.scan_subtitle'))

@section('actions')
<button type="button" id="openScanSimModal" class="ui-btn ui-btn-primary">{{ __('admin.qr.scan_simulate') }}</button>
@endsection

@section('content')
<form class="ui-toolbar">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('admin.qr.serial') }} / {{ __('admin.orders.customer') }}" class="ui-input max-w-xs">
    <select name="sync_status" class="ui-select max-w-[12rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.qr.sync_status') }}</option>
        <option value="synced" @selected(request('sync_status')==='synced')>{{ __('admin.qr.synced') }}</option>
        <option value="pending" @selected(request('sync_status')==='pending')>{{ __('admin.qr.sync_pending') }}</option>
        <option value="failed" @selected(request('sync_status')==='failed')>{{ __('admin.qr.sync_failed') }}</option>
    </select>
    <select name="is_offline" class="ui-select max-w-[10rem]" onchange="this.form.submit()">
        <option value="">{{ __('admin.all') }}</option>
        <option value="0" @selected(request('is_offline')==='0')>{{ __('admin.qr.online') }}</option>
        <option value="1" @selected(request('is_offline')==='1')>{{ __('admin.qr.offline') }}</option>
    </select>
    <select name="merchant_id" class="ui-select max-w-xs" onchange="this.form.submit()">
        <option value="">{{ __('admin.nav.merchants') }}</option>
        @foreach($merchants as $m)
            <option value="{{ $m->id }}" @selected(request('merchant_id')==$m->id)>{{ $m->business_name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="ui-input max-w-[10rem]">
    <input type="date" name="to" value="{{ request('to') }}" class="ui-input max-w-[10rem]">
    <button class="ui-btn ui-btn-dark">{{ __('admin.filter') }}</button>
</form>

<div class="space-y-3">
    @forelse($scans as $scan)
        <div class="ui-card-static p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="font-semibold">{{ $scan->customer?->user?->full_name ?? '—' }}</div>
                    <div class="mt-1 ui-muted">{{ $scan->merchant?->business_name ?? __('admin.qr.no_merchant') }}</div>
                    <div class="mt-2 font-mono text-xs" dir="ltr">{{ $scan->qrCode?->serial_code }}</div>
                </div>
                <div class="text-end">
                    <div class="font-semibold text-maqam-gold-dark">+{{ $scan->points_awarded_customer }} {{ __('admin.qr.pts_customer') }}</div>
                    <div class="ui-muted">+{{ $scan->points_awarded_merchant }} {{ __('admin.qr.pts_merchant') }}</div>
                    <div class="mt-2 flex flex-wrap justify-end gap-1">
                        <span class="ui-badge ui-badge-muted">{{ $scan->is_offline ? __('admin.qr.offline') : __('admin.qr.online') }}</span>
                        <span class="ui-badge {{ $scan->sync_status==='failed'?'ui-badge-danger':($scan->sync_status==='pending'?'ui-badge-warn':'ui-badge-ok') }}">
                            {{ __('admin.qr.'.($scan->sync_status === 'pending' ? 'sync_pending' : ($scan->sync_status === 'failed' ? 'sync_failed' : 'synced'))) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-[11px] ui-muted">
                <span>{{ $scan->scanned_at?->format('Y-m-d H:i') }}</span>
                @if($scan->scan_location_lat)
                    <span>{{ __('admin.qr.geo') }}: <span dir="ltr">{{ $scan->scan_location_lat }}, {{ $scan->scan_location_lng }}</span></span>
                @endif
                @if($scan->qrCode?->categoryPrize)
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $scan->qrCode->categoryPrize->background_color }}"></span>
                        {{ $scan->qrCode->categoryPrize->name_ar }}
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="ui-empty">{{ __('admin.qr.empty_scans') }}</div>
    @endforelse
</div>
<div class="mt-6">{{ $scans->links() }}</div>

{{-- Preview-only simulation modal --}}
<div id="scanSimModal" class="wheel-modal" aria-hidden="true">
    <div class="wheel-modal-panel max-w-lg" role="dialog" aria-modal="true" aria-labelledby="scanSimTitle">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 id="scanSimTitle" class="text-base font-semibold">{{ __('admin.qr.scan_simulate') }}</h3>
                <p class="ui-muted mt-0.5 text-xs">{{ __('admin.qr.scan_simulate_dry') }}</p>
            </div>
            <button type="button" id="closeScanSimModal" class="ui-btn ui-btn-ghost px-3 py-1.5 text-xs">{{ __('admin.close') }}</button>
        </div>

        <form id="scanSimForm" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.qr.serial') }}</label>
                <input list="active-serials" id="simSerial" name="serial_code" required maxlength="16" class="ui-input font-mono" dir="ltr" placeholder="16 digits">
                <datalist id="active-serials">
                    @foreach($activeCodes as $code)
                        <option value="{{ $code->serial_code }}">{{ $code->points_awarded }} pts · {{ $code->batch_id }}</option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.orders.customer') }}</label>
                <select id="simCustomer" name="customer_id" required class="ui-select">
                    <option value="">{{ __('admin.wheel.pick_customer') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->user?->full_name }} ({{ number_format($customer->points_balance) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ __('admin.nav.merchants') }}</label>
                <select id="simMerchant" name="merchant_id" class="ui-select">
                    <option value="">{{ __('admin.qr.no_merchant') }}</option>
                    @foreach($merchants as $m)
                        <option value="{{ $m->id }}">{{ $m->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" id="simOffline" name="is_offline" value="1">
                {{ __('admin.qr.offline') }}
            </label>
            <button type="submit" id="scanSimSubmit" class="ui-btn ui-btn-primary w-full">{{ __('admin.qr.scan_try') }}</button>
        </form>

        <div id="scanSimResult" class="mt-4 hidden rounded-xl border border-[#E4E0D7] bg-[#F9F8F5] p-4 text-sm"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('scanSimModal');
    const openBtn = document.getElementById('openScanSimModal');
    const closeBtn = document.getElementById('closeScanSimModal');
    const form = document.getElementById('scanSimForm');
    const resultEl = document.getElementById('scanSimResult');
    const submitBtn = document.getElementById('scanSimSubmit');
    const url = @json(route('admin.scans.simulate'));
    const csrf = @json(csrf_token());
    const i18n = {
        dryNote: @json(__('admin.qr.scan_preview_note')),
        ptsCustomer: @json(__('admin.qr.pts_customer')),
        ptsMerchant: @json(__('admin.qr.pts_merchant')),
        balanceNow: @json(__('admin.qr.balance_now')),
        balanceIf: @json(__('admin.qr.balance_if_real')),
        trying: @json(__('admin.qr.scan_trying')),
        tryAgain: @json(__('admin.qr.scan_try')),
    };

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        resultEl.classList.add('hidden');
        resultEl.innerHTML = '';
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultEl.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = i18n.trying;

        const body = new FormData(form);
        if (!document.getElementById('simOffline').checked) {
            body.delete('is_offline');
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await res.json();

            if (!res.ok || !data.ok) {
                resultEl.classList.remove('hidden');
                resultEl.innerHTML = `<div class="text-red-700 font-medium">${data.message || 'Error'}</div>`;
                return;
            }

            resultEl.classList.remove('hidden');
            resultEl.innerHTML = `
                <div class="mb-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">${i18n.dryNote}</div>
                <div class="font-semibold text-maqam-ink">${data.message}</div>
                <div class="mt-3 space-y-1 ui-muted">
                    <div dir="ltr"><code>${data.serial}</code> · ${data.category || '—'}</div>
                    <div>${data.customer}${data.merchant ? ' · ' + data.merchant : ''}</div>
                    <div class="text-maqam-gold-dark font-semibold">+${data.points_customer} ${i18n.ptsCustomer}</div>
                    <div>+${data.points_merchant} ${i18n.ptsMerchant}</div>
                    <div>${i18n.balanceNow}: ${data.balance_now}</div>
                    <div>${i18n.balanceIf}: ${data.balance_after_if_real}</div>
                </div>
            `;
        } catch (err) {
            resultEl.classList.remove('hidden');
            resultEl.innerHTML = `<div class="text-red-700 font-medium">${err.message || 'Error'}</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = i18n.tryAgain;
        }
    });
})();
</script>
@endpush
