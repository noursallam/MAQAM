@extends('store.layouts.app')

@php
    $locale = app()->getLocale();
    $name = $locale === 'ar' ? ($product->name_ar ?: $product->name_en) : ($product->name_en ?: $product->name_ar);
    $desc = $locale === 'ar' ? ($product->description_ar ?: $product->description_en) : ($product->description_en ?: $product->description_ar);
    $catName = $product->category ? ($locale === 'ar' ? $product->category->name_ar : $product->category->name_en) : __('store.categories.all');
    $isVariable = $product->isPriceVariable();
    $firstOption = $product->options->first();
    $initialPrice = ($firstOption && $firstOption->price !== null) ? (float) $firstOption->price : (float) $product->price;
    $points = (int) max(10, floor($initialPrice / 2));
    
    $mediaService = app(\App\Services\MediaService::class);
    $gallery = collect();

    if ($product->images && $product->images->isNotEmpty()) {
        foreach ($product->images as $img) {
            $url = $img->url();
            if ($url && !$gallery->contains('url', $url)) {
                $gallery->push([
                    'id' => $img->id,
                    'url' => $url,
                    'path' => $img->path,
                    'is_thumb' => (bool)$img->is_thumbnail,
                ]);
            }
        }
    }

    if (!empty($product->image_path)) {
        $mainUrl = $mediaService->url($product->image_path);
        if ($mainUrl && !$gallery->contains('url', $mainUrl)) {
            $gallery->prepend([
                'id' => 'main',
                'url' => $mainUrl,
                'path' => $product->image_path,
                'is_thumb' => true,
            ]);
        }
    }

    if ($gallery->isEmpty()) {
        $gallery->push([
            'id' => 'fallback',
            'url' => asset('identity/MAQAM-24.jpg'),
            'path' => null,
            'is_thumb' => true,
        ]);
    }

    $galleryTotal = $gallery->count();
    $firstImageUrl = $gallery->first()['url'];
@endphp

@section('title', $name . ' — ' . __('store.store_name'))

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('store.shop') }}">{{ __('store.nav.shop') }}</a>
            @if ($product->category)
                <span class="sep">/</span>
                <a href="{{ route('store.shop', ['category' => $product->category_id]) }}">{{ $catName }}</a>
            @endif
            <span class="sep">/</span>
            <span>{{ $name }}</span>
        </div>

        <div class="mq-product-layout">
            {{-- Interactive Multi-Image Gallery & Zoom Stage --}}
            <div class="mq-product-gallery-wrap">
                <div class="mq-gallery-stage" id="mqGalleryStage" role="region" aria-label="{{ __('store.product.details') }}">
                    <div class="mq-gallery-badges">
                        <span class="mq-gallery-zoom-hint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            <span>{{ __('store.product.hover_zoom') }}</span>
                        </span>
                        <span class="mq-gallery-counter" id="mqGalleryCounter">1 / {{ $galleryTotal }}</span>
                    </div>

                    <div class="mq-gallery-viewport" id="mqGalleryViewport" title="{{ __('store.product.click_expand') }}">
                        <img id="mqMainProductImg" 
                             src="{{ $firstImageUrl }}" 
                             alt="{{ $name }}" 
                             data-index="0"
                             draggable="false">
                    </div>

                    @if ($galleryTotal > 1)
                        <button type="button" class="mq-gallery-arrow mq-gallery-prev" id="mqGalleryPrevBtn" aria-label="{{ __('store.product.prev_image') }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button type="button" class="mq-gallery-arrow mq-gallery-next" id="mqGalleryNextBtn" aria-label="{{ __('store.product.next_image') }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @endif

                    <button type="button" class="mq-gallery-expand-btn" id="mqGalleryExpandBtn" title="{{ __('store.product.click_expand') }}" aria-label="{{ __('store.product.click_expand') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                    </button>
                </div>

                @if ($galleryTotal > 1)
                    <div class="mq-gallery-thumbs" id="mqGalleryThumbs" role="tablist" aria-label="صور المنتج">
                        @foreach ($gallery as $idx => $gImg)
                            <button type="button" 
                                    class="mq-gallery-thumb {{ $idx === 0 ? 'is-active' : '' }}" 
                                    data-index="{{ $idx }}"
                                    data-url="{{ $gImg['url'] }}"
                                    aria-label="صورة {{ $idx + 1 }}">
                                <img src="{{ $gImg['url'] }}" alt="{{ $name }} - {{ $idx + 1 }}">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mq-product-info">
                @if ($product->category)
                    <span class="mq-eyebrow">{{ $catName }}</span>
                @endif
                <h1>{{ $name }}</h1>
                <div class="mq-product-meta">
                    <span>{{ __('store.common.rating') }}: 4.9 / 5</span>
                    <span>{{ __('store.common.available') }}: {{ $product->stock_quantity }} {{ __('store.product.pieces') }}</span>
                    <span>{{ __('store.product.sku') }}: #{{ $product->sku ?: $product->id }}</span>
                </div>
                <div class="mq-product-price-box" style="display:flex;align-items:baseline;gap:.5rem;margin:.75rem 0 1rem;">
                    @if ($isVariable)
                        <span class="mq-price-from-badge" id="mqPriceSublabel" style="font-size:.85rem;color:var(--mq-muted);font-weight:600;">{{ __('store.common.from') }}</span>
                    @endif
                    <div class="mq-product-price" id="mqProductPriceDisplay" style="margin:0;font-size:1.85rem;font-weight:700;color:var(--mq-gold);transition:transform .2s ease;">
                        <span id="mqPriceValue">{{ number_format($initialPrice, 2) }}</span>
                        <span style="font-size:1rem;font-weight:500;color:inherit;">{{ __('store.common.egp') }}</span>
                    </div>
                </div>
                
                @if ($desc)
                    <p style="color:var(--mq-muted);margin:0 0 1rem;line-height:1.6;">{{ $desc }}</p>
                @endif

                <div class="mq-loyalty-inline">
                    <strong>{{ __('store.product.loyalty_badge') }}</strong>
                    <span><span id="mqPointsValue">+{{ $points }}</span> {{ __('store.common.points') }} — {{ __('store.product.loyalty_hint') }}</span>
                </div>

                <form action="{{ route('store.cart.add') }}" method="POST" id="mqProductForm">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="option_id" id="mqSelectedOptionId" value="{{ $firstOption?->id }}">

                    @if ($product->colors && $product->colors->isNotEmpty())
                        <div style="margin-bottom:1rem;">
                            <label style="display:block;margin-bottom:.4rem;font-weight:600;font-size:.9rem;">{{ __('store.product.colors') ?? 'اللون' }}:</label>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                @foreach ($product->colors as $i => $color)
                                    <label style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .75rem;background:rgba(255,255,255,.05);border:1.5px solid var(--mq-line);border-radius:6px;cursor:pointer;">
                                        <input type="radio" name="color" value="{{ $color->name }}" {{ $i === 0 ? 'checked' : '' }} style="accent-color:var(--mq-gold);">
                                        @if ($color->hex)
                                            <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $color->hex }};border:1px solid rgba(255,255,255,.2);"></span>
                                        @endif
                                        <span>{{ $color->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($product->options && $product->options->isNotEmpty())
                        <div style="margin-bottom:1.25rem;">
                            <label style="display:block;margin-bottom:.5rem;font-weight:600;font-size:.9rem;">
                                {{ __('store.product.options') ?? 'المواصفة / الخيار' }}:
                            </label>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(170px, 1fr));gap:.65rem;">
                                @foreach ($product->options as $i => $opt)
                                    @php
                                        $optPrice = $opt->price !== null ? (float) $opt->price : (float) $product->price;
                                        $hasCustomPrice = $opt->price !== null;
                                    @endphp
                                    <label class="mq-option-pill" style="display:flex;flex-direction:column;gap:.25rem;padding:.65rem .85rem;background:rgba(255,255,255,.04);border:1.5px solid var(--mq-line);border-radius:8px;cursor:pointer;transition:.2s ease;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                            <span style="font-weight:600;font-size:.9rem;">
                                                <input type="radio"
                                                       name="option"
                                                       value="{{ $opt->value }}"
                                                       data-id="{{ $opt->id }}"
                                                       data-price="{{ $optPrice }}"
                                                       onchange="updateProductOptionPrice(this)"
                                                       {{ $i === 0 ? 'checked' : '' }}
                                                       style="accent-color:var(--mq-gold);margin-inline-end:.4rem;">
                                                {{ $opt->name }}: {{ $opt->value }}
                                            </span>
                                        </div>
                                        @if ($hasCustomPrice)
                                            <div style="font-size:.82rem;color:var(--mq-gold);font-weight:700;padding-inline-start:1.4rem;">
                                                {{ number_format($optPrice, 2) }} {{ __('store.common.egp') }}
                                            </div>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
                        <div class="mq-qty" aria-label="{{ __('store.common.qty') }}">
                            <button type="button" onclick="var el=document.getElementById('mqProductQty'); var v=parseInt(el.value)||1; if(v>1) el.value=v-1;">−</button>
                            <input type="number" name="quantity" id="mqProductQty" value="1" min="1" max="{{ max(1, $product->stock_quantity) }}" style="width:50px;text-align:center;background:transparent;border:none;color:inherit;font-weight:700;">
                            <button type="button" onclick="var el=document.getElementById('mqProductQty'); var v=parseInt(el.value)||1; if(v<{{ max(1, $product->stock_quantity) }}) el.value=v+1;">+</button>
                        </div>
                    </div>

                    <div class="mq-product-actions">
                        @if ($product->stock_quantity > 0)
                            <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.product.add_cart') }}</button>
                            <button type="submit" formaction="{{ route('store.cart.add') }}" name="buy_now" value="1" class="mq-btn mq-btn-ghost">{{ __('store.product.buy_now') }}</button>
                        @else
                            <button type="button" class="mq-btn mq-btn-ghost" disabled style="opacity:.6;">{{ __('store.shop.out_of_stock') ?? 'المنتج غير متوفر حالياً' }}</button>
                        @endif
                    </div>
                </form>

                <script>
                function updateProductOptionPrice(radio) {
                    if (!radio) return;
                    const price = parseFloat(radio.dataset.price);
                    const optId = radio.dataset.id;
                    if (!isNaN(price)) {
                        const valEl = document.getElementById('mqPriceValue');
                        if (valEl) {
                            valEl.innerText = price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            valEl.style.transform = 'scale(1.08)';
                            setTimeout(() => { valEl.style.transform = 'none'; }, 200);
                        }
                        const sublabel = document.getElementById('mqPriceSublabel');
                        if (sublabel) {
                            sublabel.style.display = 'none';
                        }
                        const ptsEl = document.getElementById('mqPointsValue');
                        if (ptsEl) {
                            const pts = Math.max(10, Math.floor(price / 2));
                            ptsEl.innerText = '+' + pts;
                        }
                    }
                    const idInput = document.getElementById('mqSelectedOptionId');
                    if (idInput && optId) {
                        idInput.value = optId;
                    }
                }
                </script>

                <div class="mq-tabs" role="tablist" style="margin-top:2rem;">
                    <button type="button" class="is-active">{{ __('store.product.description') }}</button>
                </div>
                <div class="mq-panel">
                    <p style="margin:0;color:var(--mq-muted);line-height:1.7;">
                        {{ $desc ?: __('store.product.panel_text') }}
                    </p>
                </div>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <div style="margin-top:4rem;">
                <h2 style="font-size:1.4rem;margin-bottom:1.5rem;">{{ __('store.product.related') ?? 'منتجات ذات صلة' }}</h2>
                <div class="mq-products">
                    @include('store.partials.products', ['products' => $relatedProducts, 'enhanced' => true])
                </div>
            </div>
        @endif
    </div>

    {{-- Luxury Full-Screen Ultra Zoom Lightbox Modal --}}
    <div class="mq-zoom-modal" id="mqZoomModal" aria-hidden="true" role="dialog" aria-label="{{ __('store.product.click_expand') }}">
        <div class="mq-zoom-backdrop" id="mqZoomBackdrop"></div>
        <div class="mq-zoom-dialog">
            {{-- Modal Top Toolbar --}}
            <div class="mq-zoom-toolbar">
                <div class="mq-zoom-title">
                    <span class="mq-zoom-prod-name">{{ $name }}</span>
                    <span class="mq-zoom-counter" id="mqModalCounter">1 / {{ $galleryTotal }}</span>
                </div>

                <div class="mq-zoom-controls">
                    <button type="button" class="mq-zoom-tool-btn" id="mqZoomOutBtn" title="{{ __('store.product.zoom_out') }}" aria-label="{{ __('store.product.zoom_out') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>

                    <span class="mq-zoom-level-badge" id="mqZoomLevelBadge">100%</span>

                    <button type="button" class="mq-zoom-tool-btn" id="mqZoomInBtn" title="{{ __('store.product.zoom_in') }}" aria-label="{{ __('store.product.zoom_in') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>

                    <button type="button" class="mq-zoom-tool-btn" id="mqZoomResetBtn" title="{{ __('store.product.zoom_reset') }}" aria-label="{{ __('store.product.zoom_reset') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>

                    <div class="mq-zoom-tool-sep"></div>

                    <button type="button" class="mq-zoom-close-btn" id="mqZoomCloseBtn" title="{{ __('store.product.close_modal') }}" aria-label="{{ __('store.product.close_modal') }}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            {{-- Modal Interactive Viewport Stage --}}
            <div class="mq-zoom-viewport" id="mqZoomViewport">
                <div class="mq-zoom-image-container" id="mqZoomImgContainer">
                    <img id="mqZoomModalImg" src="{{ $firstImageUrl }}" alt="{{ $name }}" draggable="false">
                </div>

                @if ($galleryTotal > 1)
                    <button type="button" class="mq-modal-arrow mq-modal-prev" id="mqModalPrevBtn" aria-label="{{ __('store.product.prev_image') }}">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button type="button" class="mq-modal-arrow mq-modal-next" id="mqModalNextBtn" aria-label="{{ __('store.product.next_image') }}">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                @endif

                <div class="mq-zoom-hint-floating" id="mqZoomDragHint">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/></svg>
                    <span>{{ __('store.product.drag_hint') }}</span>
                </div>
            </div>

            {{-- Modal Bottom Thumbnail Strip --}}
            @if ($galleryTotal > 1)
                <div class="mq-zoom-modal-thumbs" id="mqModalThumbs">
                    @foreach ($gallery as $idx => $gImg)
                        <button type="button" 
                                class="mq-modal-thumb {{ $idx === 0 ? 'is-active' : '' }}" 
                                data-index="{{ $idx }}"
                                data-url="{{ $gImg['url'] }}"
                                aria-label="صورة {{ $idx + 1 }}">
                            <img src="{{ $gImg['url'] }}" alt="">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const galleryData = @json($gallery->values()->all());
    let currentIndex = 0;

    // Elements
    const mainImg = document.getElementById('mqMainProductImg');
    const stage = document.getElementById('mqGalleryStage');
    const viewport = document.getElementById('mqGalleryViewport');
    const counter = document.getElementById('mqGalleryCounter');
    const thumbs = document.querySelectorAll('.mq-gallery-thumb');
    const prevBtn = document.getElementById('mqGalleryPrevBtn');
    const nextBtn = document.getElementById('mqGalleryNextBtn');
    const expandBtn = document.getElementById('mqGalleryExpandBtn');

    // Modal elements
    const modal = document.getElementById('mqZoomModal');
    const modalBackdrop = document.getElementById('mqZoomBackdrop');
    const modalCloseBtn = document.getElementById('mqZoomCloseBtn');
    const modalImg = document.getElementById('mqZoomModalImg');
    const modalImgContainer = document.getElementById('mqZoomImgContainer');
    const modalViewport = document.getElementById('mqZoomViewport');
    const modalCounter = document.getElementById('mqModalCounter');
    const modalThumbs = document.querySelectorAll('.mq-modal-thumb');
    const modalPrevBtn = document.getElementById('mqModalPrevBtn');
    const modalNextBtn = document.getElementById('mqModalNextBtn');
    const zoomInBtn = document.getElementById('mqZoomInBtn');
    const zoomOutBtn = document.getElementById('mqZoomOutBtn');
    const zoomResetBtn = document.getElementById('mqZoomResetBtn');
    const zoomLevelBadge = document.getElementById('mqZoomLevelBadge');

    function setActiveImage(index) {
        if (!galleryData || galleryData.length === 0) return;
        if (index < 0) index = galleryData.length - 1;
        if (index >= galleryData.length) index = 0;
        currentIndex = index;

        const currentItem = galleryData[currentIndex];

        // Update main stage image with quick crossfade
        if (mainImg) {
            mainImg.style.opacity = '0.35';
            setTimeout(() => {
                mainImg.src = currentItem.url;
                mainImg.dataset.index = currentIndex;
                mainImg.style.opacity = '1';
            }, 100);
        }

        if (counter) {
            counter.innerText = (currentIndex + 1) + ' / ' + galleryData.length;
        }

        // Update stage thumbnails active class
        thumbs.forEach(t => {
            const idx = parseInt(t.dataset.index, 10);
            if (idx === currentIndex) {
                t.classList.add('is-active');
                t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                t.classList.remove('is-active');
            }
        });

        // Update modal image if open
        if (modalImg) {
            modalImg.src = currentItem.url;
        }
        if (modalCounter) {
            modalCounter.innerText = (currentIndex + 1) + ' / ' + galleryData.length;
        }
        modalThumbs.forEach(mt => {
            const idx = parseInt(mt.dataset.index, 10);
            if (idx === currentIndex) {
                mt.classList.add('is-active');
            } else {
                mt.classList.remove('is-active');
            }
        });

        // Reset modal zoom when changing image
        resetModalZoom();
    }

    // Stage Thumbnail click & hover
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', function () {
            const idx = parseInt(this.dataset.index, 10);
            setActiveImage(idx);
        });
        thumb.addEventListener('mouseenter', function () {
            const idx = parseInt(this.dataset.index, 10);
            setActiveImage(idx);
        });
    });

    // Stage Next / Prev
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            setActiveImage(currentIndex - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            setActiveImage(currentIndex + 1);
        });
    }

    // Stage Hover Loupe Zoom Pan
    if (viewport && mainImg) {
        let isHovered = false;

        viewport.addEventListener('mouseenter', function () {
            isHovered = true;
            mainImg.style.transform = 'scale(2.2)';
        });

        viewport.addEventListener('mousemove', function (e) {
            if (!isHovered) return;
            const rect = viewport.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            mainImg.style.transformOrigin = `${x.toFixed(1)}% ${y.toFixed(1)}%`;
        });

        viewport.addEventListener('mouseleave', function () {
            isHovered = false;
            mainImg.style.transform = 'scale(1)';
            mainImg.style.transformOrigin = 'center center';
        });

        // Clicking stage opens full-screen zoom modal
        viewport.addEventListener('click', function () {
            openZoomModal();
        });
    }

    if (expandBtn) {
        expandBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            openZoomModal();
        });
    }

    // -------------------------------------------------------------
    // Full-Screen Ultra Zoom Modal Logic
    // -------------------------------------------------------------
    let currentScale = 1.0;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;

    function updateModalTransform() {
        if (!modalImgContainer) return;
        modalImgContainer.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        if (zoomLevelBadge) {
            zoomLevelBadge.innerText = Math.round(currentScale * 100) + '%';
        }
        if (modalViewport) {
            if (currentScale > 1.05) {
                modalViewport.style.cursor = 'grab';
            } else {
                modalViewport.style.cursor = 'zoom-in';
            }
        }
    }

    function resetModalZoom() {
        currentScale = 1.0;
        translateX = 0;
        translateY = 0;
        updateModalTransform();
    }

    function zoomBy(delta, clientX = null, clientY = null) {
        let newScale = currentScale + delta;
        newScale = Math.max(1.0, Math.min(4.5, newScale));
        
        if (Math.abs(newScale - currentScale) < 0.01) return;

        // If zooming toward cursor
        if (clientX !== null && clientY !== null && modalViewport) {
            const rect = modalViewport.getBoundingClientRect();
            const px = clientX - (rect.left + rect.width / 2) - translateX;
            const py = clientY - (rect.top + rect.height / 2) - translateY;
            const factor = newScale / currentScale;
            translateX -= px * (factor - 1);
            translateY -= py * (factor - 1);
        }

        currentScale = newScale;
        if (currentScale <= 1.05) {
            translateX = 0;
            translateY = 0;
        }
        updateModalTransform();
    }

    function openZoomModal() {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setActiveImage(currentIndex);
        resetModalZoom();
    }

    function closeZoomModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        resetModalZoom();
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeZoomModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeZoomModal);

    if (zoomInBtn) zoomInBtn.addEventListener('click', () => zoomBy(0.4));
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => zoomBy(-0.4));
    if (zoomResetBtn) zoomResetBtn.addEventListener('click', resetModalZoom);

    // Modal Next / Prev
    if (modalPrevBtn) {
        modalPrevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            setActiveImage(currentIndex - 1);
        });
    }
    if (modalNextBtn) {
        modalNextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            setActiveImage(currentIndex + 1);
        });
    }

    // Modal thumbs click
    modalThumbs.forEach(mt => {
        mt.addEventListener('click', function () {
            const idx = parseInt(this.dataset.index, 10);
            setActiveImage(idx);
        });
    });

    // Mouse wheel zoom inside modal
    if (modalViewport) {
        modalViewport.addEventListener('wheel', function (e) {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.3 : -0.3;
            zoomBy(delta, e.clientX, e.clientY);
        }, { passive: false });

        // Double click toggles 1.0 <-> 2.2x
        modalViewport.addEventListener('dblclick', function (e) {
            e.preventDefault();
            if (currentScale > 1.2) {
                resetModalZoom();
            } else {
                zoomBy(1.2, e.clientX, e.clientY);
            }
        });

        // Drag / Pan functionality
        modalViewport.addEventListener('mousedown', function (e) {
            if (currentScale <= 1.05) return;
            isDragging = true;
            modalViewport.classList.add('is-dragging');
            modalViewport.style.cursor = 'grabbing';
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateModalTransform();
        });

        window.addEventListener('mouseup', function () {
            if (isDragging) {
                isDragging = false;
                if (modalViewport) {
                    modalViewport.classList.remove('is-dragging');
                    modalViewport.style.cursor = currentScale > 1.05 ? 'grab' : 'zoom-in';
                }
            }
        });

        // Touch gestures for mobile
        let touchStartDist = 0;
        let touchStartX = 0;
        let touchStartY = 0;

        modalViewport.addEventListener('touchstart', function (e) {
            if (e.touches.length === 1) {
                if (currentScale > 1.05) {
                    isDragging = true;
                    touchStartX = e.touches[0].clientX - translateX;
                    touchStartY = e.touches[0].clientY - translateY;
                }
            } else if (e.touches.length === 2) {
                touchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
            }
        }, { passive: true });

        modalViewport.addEventListener('touchmove', function (e) {
            if (e.touches.length === 1 && isDragging) {
                translateX = e.touches[0].clientX - touchStartX;
                translateY = e.touches[0].clientY - touchStartY;
                updateModalTransform();
            } else if (e.touches.length === 2 && touchStartDist > 0) {
                const newDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const scaleDelta = (newDist - touchStartDist) * 0.006;
                zoomBy(scaleDelta);
                touchStartDist = newDist;
            }
        }, { passive: true });

        modalViewport.addEventListener('touchend', function () {
            isDragging = false;
            touchStartDist = 0;
        });
    }

    // Keyboard navigation
    window.addEventListener('keydown', function (e) {
        if (!modal || !modal.classList.contains('is-open')) return;

        if (e.key === 'Escape') {
            closeZoomModal();
        } else if (e.key === 'ArrowRight') {
            setActiveImage(currentIndex - 1);
        } else if (e.key === 'ArrowLeft') {
            setActiveImage(currentIndex + 1);
        } else if (e.key === '+' || e.key === '=') {
            zoomBy(0.4);
        } else if (e.key === '-' || e.key === '_') {
            zoomBy(-0.4);
        } else if (e.key === '0') {
            resetModalZoom();
        }
    });
});
</script>
@endsection
