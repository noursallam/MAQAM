@extends('store.layouts.app')

@section('title', __('store.faq.title'))

@section('content')
<section class="mq-page mq-home-faq">
    <div class="mq-container mq-home-faq-inner">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.faq.heading') }}</span>
        </div>
        <div class="mq-section-head">
            <span class="mq-eyebrow">{{ __('store.faq.heading') }}</span>
            <h2>{{ __('store.faq.title_line') }} <em>{{ __('store.faq.title_em') }}</em></h2>
            <p>{{ __('store.faq.lead') }}</p>
        </div>

        <div class="mq-faq-list mq-faq-accordion">
            @foreach (['q1', 'q2', 'q3', 'q7', 'q5', 'q6'] as $key)
                <details class="mq-faq-item">
                    <summary>
                        <span>{{ __('store.faq.'.$key) }}</span>
                        <i class="mq-faq-chevron" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </i>
                    </summary>
                    <p>{{ __('store.faq.a'.substr($key, 1)) }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endsection
