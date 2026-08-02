@extends('store.layouts.app')

@section('title', $title.' | '.__('store.brand'))

@section('content')
<section class="mq-page">
    <div class="mq-container mq-legal">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ $title }}</span>
        </div>
        <h1 class="mq-page-title">{{ $title }}</h1>
        @if (!empty($lead))
            <p class="mq-page-lead">{{ $lead }}</p>
        @endif
        <div class="mq-panel mq-prose">
            {!! $body !!}
        </div>
    </div>
</section>
@endsection
