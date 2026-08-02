@extends('store.layouts.app')

@section('title', __('store.blog.title'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.blog.heading') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.blog.heading') }}</h1>
        <p class="mq-page-lead">{{ __('store.blog.lead') }}</p>

        <div class="mq-blog-grid">
            @foreach ([
                ['title_key' => 'post_1', 'date' => '15 Jul 2026'],
                ['title_key' => 'post_2', 'date' => '10 Jul 2026'],
                ['title_key' => 'post_3', 'date' => '2 Jul 2026'],
            ] as $post)
                <article class="mq-blog-card">
                    <div class="mq-blog-cover"></div>
                    <div class="body">
                        <div class="date">{{ $post['date'] }}</div>
                        <h3>{{ __('store.blog.'.$post['title_key']) }}</h3>
                        <p style="margin:0;color:var(--mq-muted);font-size:.9rem">{{ __('store.blog.excerpt') }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
