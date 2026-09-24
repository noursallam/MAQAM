@php
    $locale = app()->getLocale();
    $enhanced = $enhanced ?? false;
@endphp

@forelse ($products as $p)
    @php
        $name = $locale === 'ar' ? ($p->name_ar ?: $p->name_en) : ($p->name_en ?: $p->name_ar);
        $catName = $p->category ? ($locale === 'ar' ? $p->category->name_ar : $p->category->name_en) : __('store.categories.all');
        $price = number_format((float) $p->price, 2) . ' ' . __('store.common.egp');
        
        $points = (int) max(10, floor((float) $p->price / 2));
        $imgUrl = null;
        if (!empty($p->image_path)) {
            $imgUrl = asset('storage/' . $p->image_path);
        } elseif ($p->thumbnail && !empty($p->thumbnail->path)) {
            $imgUrl = asset('storage/' . $p->thumbnail->path);
        } elseif ($p->images && $p->images->isNotEmpty()) {
            $imgUrl = asset('storage/' . $p->images->first()->path);
        }

        $tag = null;
        if ($p->stock_quantity <= 0) {
            $tag = __('store.shop.out_of_stock', [], $locale) ?: 'غير متوفر';
        } elseif ($p->created_at && $p->created_at->gt(now()->subDays(30))) {
            $tag = __('store.common.new');
        }
    @endphp
    <article class="mq-card {{ $enhanced ? 'mq-card-shop' : '' }}">
        <a href="{{ route('store.product', $p->id) }}" class="mq-card-media" style="background:#1b2434" aria-label="{{ $name }}">
            @if ($tag)
                <span class="mq-card-tag {{ $p->stock_quantity <= 0 ? 'is-out' : '' }}">{{ $tag }}</span>
            @endif
            @if ($points > 0)
                <span class="mq-card-points">+{{ $points }} {{ __('store.common.points') }}</span>
            @endif

            @if ($imgUrl)
                <img src="{{ $imgUrl }}" alt="{{ $name }}" class="mq-card-img" style="width:100%;height:100%;object-fit:cover;">
            @else
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.2" aria-hidden="true">
                    <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
                </svg>
            @endif
        </a>
        <div class="mq-card-body">
            <div class="mq-card-top">
                <span class="mq-card-cat">{{ $catName }}</span>
                @if ($enhanced)
                    <button type="button" class="mq-card-wish" aria-label="{{ __('store.common.add_to_wishlist') }}" onclick="this.classList.toggle('is-active')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                    </button>
                @endif
            </div>
            <h3 class="mq-card-title">
                <a href="{{ route('store.product', $p->id) }}">{{ $name }}</a>
            </h3>
            @if ($enhanced)
                <div class="mq-card-rating">
                    <span class="stars" aria-hidden="true">★★★★★</span>
                    <span>4.9</span>
                    <span class="reviews">({{ $p->stock_quantity }} {{ __('store.common.available') }})</span>
                </div>
            @endif
            <div class="mq-card-foot">
                <div class="mq-card-price">
                    <span>{{ $price }}</span>
                </div>
                <form action="{{ route('store.cart.add') }}" method="POST" class="mq-ajax-add-cart" style="display:inline;">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="mq-card-cart" aria-label="{{ __('store.common.add_to_cart') }}" title="{{ __('store.common.add_to_cart') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </article>
@empty
    <div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;color:var(--mq-muted);">
        <p style="font-size:1.1rem;margin:0 0 1rem;">{{ __('store.common.no_products_found', [], $locale) ?: 'لا توجد منتجات مطابقة في هذا القسم حالياً.' }}</p>
        <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">{{ __('store.common.clear_all') }}</a>
    </div>
@endforelse
