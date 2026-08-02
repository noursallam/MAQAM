@php
    $products = $products ?? [
        ['id' => 1, 'name_key' => 'wall_socket', 'cat_key' => 'sockets', 'price' => '45', 'tag' => null, 'tone' => '#1f2937', 'rating' => '4.8', 'reviews' => 126, 'points' => 50],
        ['id' => 2, 'name_key' => 'multi_plug', 'cat_key' => 'sockets', 'price' => '85', 'old' => '110', 'tag' => '-23%', 'tone' => '#243044', 'rating' => '4.6', 'reviews' => 89, 'points' => 80],
        ['id' => 3, 'name_key' => 'light_switch', 'cat_key' => 'switches', 'price' => '35', 'tag' => null, 'tone' => '#1a2332', 'rating' => '4.7', 'reviews' => 54, 'points' => 40],
        ['id' => 4, 'name_key' => 'cable', 'cat_key' => 'cables', 'price' => '220', 'tag' => 'new', 'tone' => '#222b3b', 'rating' => '4.9', 'reviews' => 210, 'points' => 120],
        ['id' => 5, 'name_key' => 'led_bulb', 'cat_key' => 'lighting', 'price' => '55', 'tag' => null, 'tone' => '#1d2636', 'rating' => '4.5', 'reviews' => 73, 'points' => 30],
        ['id' => 6, 'name_key' => 'breaker', 'cat_key' => 'breakers', 'price' => '95', 'old' => '120', 'tag' => '-20%', 'tone' => '#202938', 'rating' => '4.4', 'reviews' => 41, 'points' => 70],
        ['id' => 7, 'name_key' => 'adapter', 'cat_key' => 'tools', 'price' => '150', 'tag' => null, 'tone' => '#182131', 'rating' => '4.8', 'reviews' => 97, 'points' => 90],
        ['id' => 8, 'name_key' => 'junction_box', 'cat_key' => 'tools', 'price' => '75', 'tag' => 'featured', 'tone' => '#1b2434', 'rating' => '4.9', 'reviews' => 38, 'points' => 60],
    ];
    $enhanced = $enhanced ?? false;
@endphp

@foreach ($products as $p)
    @php
        $name = __('store.products.'.$p['name_key']);
        $cat = __('store.categories.'.$p['cat_key']);
        $tag = match ($p['tag'] ?? null) {
            'new' => __('store.common.new'),
            'featured' => __('store.common.featured'),
            default => $p['tag'] ?? null,
        };
        $price = $p['price'].' '.__('store.common.egp');
        $old = !empty($p['old']) ? $p['old'].' '.__('store.common.egp') : null;
    @endphp
    <article class="mq-card {{ $enhanced ? 'mq-card-shop' : '' }}">
        <a href="{{ route('store.product', $p['id']) }}" class="mq-card-media" style="background:{{ $p['tone'] }}" aria-label="{{ $name }}">
            @if ($tag)
                <span class="mq-card-tag">{{ $tag }}</span>
            @endif
            @if (!empty($p['points']))
                <span class="mq-card-points">+{{ $p['points'] }} {{ __('store.common.points') }}</span>
            @endif
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.2" aria-hidden="true">
                <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
            </svg>
        </a>
        <div class="mq-card-body">
            <div class="mq-card-top">
                <span class="mq-card-cat">{{ $cat }}</span>
                @if ($enhanced)
                    <button type="button" class="mq-card-wish" aria-label="{{ __('store.common.add_to_wishlist') }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                    </button>
                @endif
            </div>
            <h3 class="mq-card-title">
                <a href="{{ route('store.product', $p['id']) }}">{{ $name }}</a>
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
                    <span>{{ $price }}</span>
                    @if ($old)
                        <span class="old">{{ $old }}</span>
                    @endif
                </div>
                @if ($enhanced)
                    <a href="{{ route('store.cart') }}" class="mq-card-cart" aria-label="{{ __('store.common.add_to_cart') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    </a>
                @endif
            </div>
        </div>
    </article>
@endforeach
