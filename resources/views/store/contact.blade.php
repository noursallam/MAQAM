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
            <form class="mq-panel" action="{{ route('store.contact.submit') }}" method="POST">
                @csrf
                <div class="mq-field">
                    <label for="contact_name">{{ __('store.contact.name') }} *</label>
                    <input type="text" id="contact_name" name="name" value="{{ old('name', auth()->user()?->full_name) }}" placeholder="{{ __('store.contact.name_ph') }}" required>
                </div>
                <div class="mq-field">
                    <label for="contact_email">{{ __('store.contact.email') }} *</label>
                    <input type="email" id="contact_email" name="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="name@email.com" dir="ltr" required>
                </div>
                <div class="mq-field">
                    <label for="contact_subject">{{ __('store.contact.subject') }} *</label>
                    <input type="text" id="contact_subject" name="subject" value="{{ old('subject') }}" placeholder="{{ __('store.contact.subject_ph') }}" required>
                </div>
                <div class="mq-field">
                    <label for="contact_message">{{ __('store.contact.message') }} *</label>
                    <textarea id="contact_message" name="message" rows="4" placeholder="{{ __('store.contact.message_ph') }}" required>{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.contact.send') }}</button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">{{ __('store.contact.info') }}</h3>
                <p style="color:var(--mq-muted)">
                    {{ __('store.contact.phone') }}: 
                    <a href="tel:{{ $contact['phone'] ?? '01001234567' }}" dir="ltr">{{ $contact['phone'] ?? '01001234567' }}</a>
                </p>
                <p style="color:var(--mq-muted)">
                    {{ __('store.contact.email_label') }}: 
                    <a href="mailto:{{ $contact['email'] ?? 'support@maqam-eg.com' }}">{{ $contact['email'] ?? 'support@maqam-eg.com' }}</a>
                </p>
                <p style="color:var(--mq-muted)">
                    العنوان: {{ $contact['address'] ?? 'القاهرة، جمهورية مصر العربية' }}
                </p>
                <a href="https://wa.me/{{ $contact['whatsapp'] ?? '201001234567' }}" class="mq-btn mq-btn-ghost" style="margin-top:.75rem;" target="_blank" rel="noopener">
                    {{ __('store.contact.via_whatsapp') }}
                </a>
            </aside>
        </div>
    </div>
</section>
@endsection
