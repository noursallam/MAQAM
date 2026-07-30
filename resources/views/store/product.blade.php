@extends('store.layouts.app')

@section('title', 'تفاصيل المنتج | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <a href="{{ route('store.shop') }}">المتجر</a>
            <span class="sep">/</span>
            <span>بريزة جدارية ثلاثية</span>
        </div>

        <div class="mq-product-layout">
            <div class="mq-product-gallery">
                <svg width="140" height="140" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.1" aria-hidden="true">
                    <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/>
                </svg>
            </div>

            <div class="mq-product-info">
                <span class="mq-eyebrow">فيش وبرايز</span>
                <h1>بريزة جدارية ثلاثية</h1>
                <div class="mq-product-meta">
                    <span>التقييم: ٤.٨ / ٥</span>
                    <span>المتوفر: ٤٨ قطعة</span>
                    <span>رقم المنتج: #{{ $productId ?? 1 }}</span>
                </div>
                <div class="mq-product-price">٤٥ ج.م</div>
                <p style="color:var(--mq-muted);margin:0 0 1rem">بريزة جدارية بجودة تركيب موثوقة للاستخدام المنزلي والتجاري. داخل العبوة كود QR لنقاط الولاء عبر تطبيق مقام.</p>

                <div class="mq-loyalty-inline">
                    <strong>+٥٠ نقطة ولاء</strong>
                    <span>بعد مسح الكود من التطبيق مع كود التاجر</span>
                </div>

                <div class="mq-qty" aria-label="الكمية">
                    <button type="button">−</button>
                    <span>1</span>
                    <button type="button">+</button>
                </div>

                <div class="mq-product-actions">
                    <a href="{{ route('store.cart') }}" class="mq-btn mq-btn-primary">أضف إلى السلة</a>
                    <a href="{{ route('store.checkout') }}" class="mq-btn mq-btn-ghost">اشترِ الآن</a>
                </div>

                <div class="mq-tabs" role="tablist">
                    <button type="button" class="is-active">الوصف</button>
                    <button type="button">المواصفات</button>
                    <button type="button">الولاء</button>
                </div>
                <div class="mq-panel">
                    <p style="margin:0;color:var(--mq-muted)">منتج كهربائي جاهز للربط مع المخزون والإدارة. النقاط ثابتة حسب فئة الهدايا المرتبطة بالكود داخل العبوة، وليست مرتبطة بسعر المنتج مباشرة.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
