@extends('admin.layouts.app')

@section('title', __('admin.whatsapp.title'))
@section('subtitle', __('admin.whatsapp.subtitle'))

@section('content')
@if(! $configured)
    <div class="ui-empty">
        <p class="ui-muted">{{ __('admin.whatsapp.not_configured') }}</p>
    </div>
@else
<div class="grid gap-6 xl:grid-cols-3" x-data="whatsappLink()" x-init="refresh()">
    <div class="ui-card-static p-6 xl:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="ui-section-title" x-text="loading ? @js(__('admin.whatsapp.checking')) : (connected ? @js(__('admin.whatsapp.connected')) : @js(__('admin.whatsapp.disconnected')))"></h2>
                <p class="ui-muted mt-1" x-show="!loading" x-text="connected ? @js(__('admin.whatsapp.connected_hint')) : @js(__('admin.whatsapp.disconnected_hint'))"></p>
            </div>
            <span class="ui-badge" x-show="!loading" :class="connected ? 'ui-badge-ok' : 'ui-badge-warn'" x-text="connected ? @js(__('admin.whatsapp.connected')) : @js(__('admin.whatsapp.disconnected'))"></span>
        </div>

        <p class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-show="error" x-text="error" x-cloak></p>

        <div class="mt-6" x-show="connected" x-cloak>
            <div class="rounded-xl border border-[#D8D4CB] bg-[#F3F1EB] p-4 text-sm">
                <div class="font-medium" x-text="name || '—'"></div>
                <div class="ui-muted" dir="ltr" x-text="phone ? '+' + phone : ''"></div>
            </div>
            <button type="button" class="ui-btn ui-btn-dark !bg-red-600 !border-red-600 mt-4" @click="disconnect()" :disabled="busy">{{ __('admin.whatsapp.disconnect') }}</button>
        </div>

        <div class="mt-6" x-show="!loading && !connected" x-cloak>
            <div class="flex flex-col items-center gap-3" x-show="qr">
                <img :src="qr" alt="QR" class="h-72 w-72 rounded-xl border border-[#D8D4CB] bg-white p-2">
                <p class="ui-muted">{{ __('admin.whatsapp.waiting_scan') }}</p>
            </div>
            <div class="mt-4 flex justify-center">
                <button type="button" class="ui-btn ui-btn-primary" @click="connect()" :disabled="busy"
                        x-text="busy ? @js(__('admin.whatsapp.generating')) : (qr ? @js(__('admin.whatsapp.refresh_qr')) : @js(__('admin.whatsapp.generate_qr')))"></button>
            </div>
        </div>
    </div>

    <div class="ui-card-static p-6 xl:col-span-1">
        <h2 class="mb-4 ui-section-title">{{ __('admin.whatsapp.steps_title') }}</h2>
        <ol class="list-decimal space-y-2 ps-5 text-sm">
            <li>{{ __('admin.whatsapp.step_1') }}</li>
            <li>{{ __('admin.whatsapp.step_2') }}</li>
            <li>{{ __('admin.whatsapp.step_3') }}</li>
        </ol>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function whatsappLink() {
    return {
        loading: true,
        busy: false,
        connected: false,
        phone: null,
        name: null,
        qr: null,
        error: '',
        timer: null,

        async call(url, method = 'GET') {
            const res = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok || !body.ok) {
                throw new Error(body.message || @js(__('admin.whatsapp.error')));
            }
            return body;
        },

        async refresh() {
            try {
                const s = await this.call(@js(route('admin.whatsapp.status')));
                this.connected = s.connected;
                this.phone = s.phone;
                this.name = s.name;
                if (s.connected) {
                    this.qr = null;
                    this.stopPolling();
                }
                this.error = '';
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async connect() {
            this.busy = true;
            this.error = '';
            try {
                const r = await this.call(@js(route('admin.whatsapp.connect')), 'POST');
                this.qr = r.qr;
                this.startPolling();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        async disconnect() {
            if (!confirm(@js(__('admin.whatsapp.disconnect_confirm')))) return;
            this.busy = true;
            this.error = '';
            try {
                await this.call(@js(route('admin.whatsapp.disconnect')), 'DELETE');
                this.connected = false;
                this.phone = this.name = null;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        startPolling() {
            this.stopPolling();
            this.timer = setInterval(() => this.refresh(), 3000);
        },

        stopPolling() {
            clearInterval(this.timer);
            this.timer = null;
        },
    };
}
</script>
@endpush
