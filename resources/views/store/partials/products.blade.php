@php
    $products = $products ?? [
        ['id' => 1, 'name' => 'بريزة جدارية ثلاثية', 'cat' => 'فيش وبرايز', 'price' => '٤٥ ج.م', 'tag' => null, 'tone' => '#1f2937', 'rating' => '٤.٨', 'reviews' => 126, 'points' => 50],
        ['id' => 2, 'name' => 'فيشة متعددة المنافذ', 'cat' => 'فيش وبرايز', 'price' => '٨٥ ج.م', 'old' => '١١٠ ج.م', 'tag' => '-23%', 'tone' => '#243044', 'rating' => '٤.٦', 'reviews' => 89, 'points' => 80],
        ['id' => 3, 'name' => 'مفتاح إنارة ثنائي', 'cat' => 'مفاتيح', 'price' => '٣٥ ج.م', 'tag' => null, 'tone' => '#1a2332', 'rating' => '٤.٧', 'reviews' => 54, 'points' => 40],
        ['id' => 4, 'name' => 'سلك كهرباء ٣×٢٫٥ مم', 'cat' => 'كابلات وأسلاك', 'price' => '٢٢٠ ج.م', 'tag' => 'جديد', 'tone' => '#222b3b', 'rating' => '٤.٩', 'reviews' => 210, 'points' => 120],
        ['id' => 5, 'name' => 'لمبة LED ١٢ وات', 'cat' => 'إضاءة', 'price' => '٥٥ ج.م', 'tag' => null, 'tone' => '#1d2636', 'rating' => '٤.٥', 'reviews' => 73, 'points' => 30],
        ['id' => 6, 'name' => 'قاطع كهرباء ٣٢ أمبير', 'cat' => 'قواطع ولوحات', 'price' => '٩٥ ج.م', 'old' => '١٢٠ ج.م', 'tag' => '-20%', 'tone' => '#202938', 'rating' => '٤.٤', 'reviews' => 41, 'points' => 70],
        ['id' => 7, 'name' => 'محول كهربائي متعدد', 'cat' => 'أدوات كهربائية', 'price' => '١٥٠ ج.م', 'tag' => null, 'tone' => '#182131', 'rating' => '٤.٨', 'reviews' => 97, 'points' => 90],
        ['id' => 8, 'name' => 'علبة توزيع بلاستيك', 'cat' => 'أدوات كهربائية', 'price' => '٧٥ ج.م', 'tag' => 'مميز', 'tone' => '#1b2434', 'rating' => '٤.٩', 'reviews' => 38, 'points' => 60],
    ];
    $enhanced = $enhanced ?? false;
@endphp

@foreach ($products as $p)
    <article class="mq-card {{ $enhanced ? 'mq-card-shop' : '' }}">
        <a href="{{ route('store.product', $p['id']) }}" class="mq-card-media" style="background:{{ $p['tone'] }}" aria-label="{{ $p['name'] }}">
            @if (!empty($p['tag']))
                <span class="mq-card-tag">{{ $p['tag'] }}</span>
            @endif
            @if (!empty($p['points']))
                <span class="mq-card-points">+{{ $p['points'] }} نقطة</span>
            @endif
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.2" aria-hidden="true">
                <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
            </svg>
        </a>
        <div class="mq-card-body">
            <div class="mq-card-top">
                <span class="mq-card-cat">{{ $p['cat'] }}</span>
                @if ($enhanced)
                    <button type="button" class="mq-card-wish" aria-label="أضف للمفضلة">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                    </button>
                @endif
            </div>
            <h3 class="mq-card-title">
                <a href="{{ route('store.product', $p['id']) }}">{{ $p['name'] }}</a>
            </h3>
            @if ($enhanced && !empty($p['rating']))
                <div class="mq-card-rating">
                    <span class="stars" aria-hidden="true">★★★★★</span>
                    <span>{{ $p['rating'] }}</span>
                    <span class="reviews">({{ $p['reviews'] }})</span>
                </div>
            @endif
            <div class="mq-card-foot">
                <div class="mq-card-price">
                    <span>{{ $p['price'] }}</span>
                    @if (!empty($p['old']))
                        <span class="old">{{ $p['old'] }}</span>
                    @endif
                </div>
                @if ($enhanced)
                    <a href="{{ route('store.cart') }}" class="mq-card-cart" aria-label="أضف للسلة">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    </a>
                @endif
            </div>
        </div>
    </article>
@endforeach
