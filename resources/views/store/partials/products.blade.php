@php
    $locale = app()->getLocale();
    $enhanced = $enhanced ?? false;
@endphp

@forelse ($products as $p)
    @php
        $name = $locale === 'ar' ? ($p->name_ar ?: $p->name_en) : ($p->name_en ?: $p->name_ar);
        $catName = $p->category ? ($locale === 'ar' ? $p->category->name_ar : $p->category->name_en) : __('store.categories.all');
        $isVariable = $p->isPriceVariable();
        $displayPrice = $isVariable ? $p->minPrice() : (float) $p->price;
        $price = number_format($displayPrice, 2) . ' ' . __('store.common.egp');
        
        $points = (int) max(10, floor($displayPrice / 2));
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
                    @if ($isVariable)
                        <span class="mq-price-from">{{ __('store.common.from') }}</span>
                    @endif
                    <span>{{ $price }}</span>
                </div>
                @if ($isVariable)
                    <a href="{{ route('store.product', $p->id) }}" class="mq-card-cart mq-card-options-link" aria-label="{{ __('store.common.view_options') }}" title="{{ __('store.common.view_options') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </a>
                @else
                    <form action="{{ route('store.cart.add') }}" method="POST" class="mq-ajax-add-cart" style="display:inline;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $p->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="mq-card-cart" aria-label="{{ __('store.common.add_to_cart') }}" title="{{ __('store.common.add_to_cart') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="mq-shop-empty-state">
        <div class="mq-empty-card">
            <div class="mq-empty-icon-wrap" aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.35-4.35"/>
                    <line x1="8" y1="11" x2="14" y2="11"/>
                </svg>
            </div>
            <h3 class="mq-empty-title">{{ __('store.common.no_products_found') }}</h3>
            <p class="mq-empty-desc">
                {{ $locale === 'ar' ? 'لم نعثر على أي منتجات مطابقة في هذا القسم حالياً. جرّب مسح الفلاتر أو تصفح الأقسام والمنتجات المقترحة أدناه.' : 'No products matched your criteria in this section. Try clearing filters or explore the recommended items below.' }}
            </p>
            <div class="mq-empty-actions">
                <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    <span>{{ __('store.common.clear_all') }}</span>
                </a>
                <a href="{{ route('store.home') }}" class="mq-btn mq-btn-ghost">
                    <span>{{ __('store.common.home') }}</span>
                </a>
            </div>
        </div>

        @if (!empty($recommendedProducts) && $recommendedProducts->isNotEmpty())
            <div class="mq-empty-recommendations">
                <div class="mq-empty-rec-head">
                    <h4>{{ $locale === 'ar' ? 'منتجات مختارة لك' : 'Recommended For You' }}</h4>
                    <span class="mq-empty-rec-line" aria-hidden="true"></span>
                </div>
                <div class="mq-products mq-shop-grid">
                    @include('store.partials.products', ['products' => $recommendedProducts, 'enhanced' => true, 'recommendedProducts' => null])
                </div>
            </div>
        @endif
    </div>
@endforelse
