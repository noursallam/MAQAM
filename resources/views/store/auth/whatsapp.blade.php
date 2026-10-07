@extends('store.layouts.app')

@section('title', __('store.auth.login_title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">{{ __('store.auth.wa_heading') }}</h1>

            <div class="mq-panel" id="wa-step-send" @if($otpSent) hidden @endif>
                <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.wa_send_lead') }}</p>
                <div dir="ltr" style="text-align:center;font-size:1.6rem;font-weight:700;letter-spacing:.08em;margin:1rem 0;">{{ $code }}</div>
                <a class="mq-btn mq-btn-primary mq-btn-block" target="_blank" rel="noopener"
                   href="https://wa.me/{{ $businessNumber }}?text={{ urlencode($code) }}">{{ __('store.auth.wa_open') }}</a>
                <p id="wa-status" style="text-align:center;margin-top:1rem;font-size:.9rem;opacity:.75;">{{ __('store.auth.wa_waiting') }}</p>
            </div>

            <form class="mq-panel" id="wa-step-otp" action="{{ route('store.login.whatsapp.verify') }}" method="POST" @unless($otpSent) hidden @endunless>
                @csrf
                <p class="mq-page-lead" style="text-align:center">{{ __('store.auth.wa_otp_lead') }}</p>
                <div class="mq-field">
                    <label for="wa_otp">{{ __('store.auth.wa_otp_label') }}</label>
                    <input type="text" id="wa_otp" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" placeholder="••••••" dir="ltr" required>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">{{ __('store.auth.login_heading') }}</button>
            </form>

            <div class="mq-auth-alt">
                <a href="{{ route('store.login') }}">{{ __('store.auth.wa_back') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection

@unless($otpSent)
@push('scripts')
<script>
(function () {
    const stepSend = document.getElementById('wa-step-send');
    const stepOtp = document.getElementById('wa-step-otp');
    const status = document.getElementById('wa-status');

    const timer = setInterval(async () => {
        try {
            const res = await fetch(@js(route('store.login.whatsapp.check')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const body = await res.json().catch(() => ({}));

            if (body.expired) {
                window.location = @js(route('store.login'));
            } else if (body.sent) {
                clearInterval(timer);
                stepSend.hidden = true;
                stepOtp.hidden = false;
                document.getElementById('wa_otp').focus();
            } else if (body.message) {
                status.textContent = body.message;
            }
        } catch (e) {
            // transient network error: keep polling
        }
    }, 4000);
})();
</script>
@endpush
@endunless
