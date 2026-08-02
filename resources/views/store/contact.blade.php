@extends('store.layouts.app')

@section('title', __('store.contact.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.contact.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.contact.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.contact.lead') }}</p>

        <div class="mq-contact-grid">
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>{{ __('store.contact.name') }}</label>
                    <input type="text" placeholder="{{ __('store.contact.name_ph') }}">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.contact.email') }}</label>
                    <input type="email" placeholder="name@email.com" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.contact.subject') }}</label>
                    <input type="text" placeholder="{{ __('store.contact.subject_ph') }}">
                </div>
                <div class="mq-field">
                    <label>{{ __('store.contact.message') }}</label>
                    <textarea placeholder="{{ __('store.contact.message_ph') }}"></textarea>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.contact.send') }}</button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">{{ __('store.contact.info') }}</h3>
                <p style="color:var(--mq-muted)">{{ __('store.contact.phone') }}: <a href="tel:+1001234567890" dir="ltr">+100 123 456 7890</a></p>
                <p style="color:var(--mq-muted)">{{ __('store.contact.whatsapp_hours') }}</p>
                <p style="color:var(--mq-muted)">{{ __('store.contact.email_label') }}: <a href="mailto:support@maqam.com">support@maqam.com</a></p>
                <a href="https://wa.me/1001234567890" class="mq-btn mq-btn-ghost" style="margin-top:.5rem" target="_blank" rel="noopener">{{ __('store.contact.via_whatsapp') }}</a>
            </aside>
        </div>
    </div>
</section>
@endsection
