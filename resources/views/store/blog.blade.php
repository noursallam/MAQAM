@extends('store.layouts.app')

@section('title', 'المدونة | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>المدونة</span>
        </div>
        <h1 class="mq-page-title">المدونة</h1>
        <p class="mq-page-lead">مقالات ونصائح من فريق مقام.</p>

        <div class="mq-blog-grid">
            @foreach ([
                ['title' => 'كيف تختار منتجك بثقة؟', 'date' => '١٥ يوليو ٢٠٢٦'],
                ['title' => 'دليل العناية بالأجهزة الذكية', 'date' => '١٠ يوليو ٢٠٢٦'],
                ['title' => 'عروض الصيف وكيف تستفيد منها', 'date' => '٢ يوليو ٢٠٢٦'],
            ] as $post)
                <article class="mq-blog-card">
                    <div class="mq-blog-cover"></div>
                    <div class="body">
                        <div class="date">{{ $post['date'] }}</div>
                        <h3>{{ $post['title'] }}</h3>
                        <p style="margin:0;color:var(--mq-muted);font-size:.9rem">محتوى تجريبي جاهز لربط المقالات لاحقًا مع لوحة التحكم.</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
