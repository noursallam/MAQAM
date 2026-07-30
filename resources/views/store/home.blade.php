@extends('store.layouts.app')

@section('title', 'مقام | أدوات كهربائية ونظام ولاء')
@section('meta_description', 'متجر مقام للأدوات الكهربائية — فيش وبرايز ومفاتيح وكابلات مع نظام ولاء ونقاط عبر مسح QR')

@section('content')
<section class="mq-hero" aria-label="العروض الرئيسية">
    <div class="mq-hero-media">
        <img src="{{ asset('store/img/hero-1.webp') }}" alt="تشكيلة مقام للأدوات الكهربائية">
    </div>
    <div class="mq-hero-glow" aria-hidden="true"></div>

    <div class="mq-container mq-hero-content">
        <h1>أدوات كهربائية موثوقة… ونقاط ولاء مع كل منتج</h1>
        <p>فيش، برايز، مفاتيح، كابلات والمزيد — اشترِ وامسح كود QR من التطبيق لتجمع نقاطك.</p>
        <div class="mq-hero-actions">
            <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-primary">تسوق الآن</a>
            <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">نظام الولاء</a>
        </div>
    </div>

    <div class="mq-hero-nav" aria-hidden="true">
        <button type="button" aria-label="السابق">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </button>
        <button type="button" aria-label="التالي">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
    </div>
</section>

<section class="mq-features">
    <div class="mq-container mq-features-grid">
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
            </div>
            <div>
                <strong>شحن سريع</strong>
                <span>توصيل لجميع المحافظات</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-3z"/></svg>
            </div>
            <div>
                <strong>منتجات مطابقة للمواصفات</strong>
                <span>جودة للتركيب والاستخدام</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
            </div>
            <div>
                <strong>نقاط ولاء بكل منتج</strong>
                <span>امسح QR من التطبيق</span>
            </div>
        </div>
        <div class="mq-feature">
            <div class="mq-feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14v3a3 3 0 0 0 3 3h1"/><path d="M20 14v3a3 3 0 0 1-3 3h-1"/><path d="M8 21h8"/><path d="M7 4h10a2 2 0 0 1 2 2v5H5V6a2 2 0 0 1 2-2z"/></svg>
            </div>
            <div>
                <strong>دعم فني ومتابعة</strong>
                <span>طلبات وشحن واستبدال</span>
            </div>
        </div>
    </div>
</section>

<section class="mq-section">
    <div class="mq-container">
        <div class="mq-section-head">
            <span class="mq-eyebrow">الأكثر طلبًا</span>
            <h2>مستلزمات كهربائية مختارة</h2>
            <p>فيش وبرايز ومفاتيح وكابلات — مع نقاط ولاء داخل كل عبوة.</p>
        </div>

        <div class="mq-products">
            @include('store.partials.products', [
                'products' => array_slice([
                    ['id' => 1, 'name' => 'بريزة جدارية ثلاثية', 'cat' => 'فيش وبرايز', 'price' => '٤٥ ج.م', 'tag' => null, 'tone' => '#1f2937', 'points' => 50],
                    ['id' => 2, 'name' => 'فيشة متعددة المنافذ', 'cat' => 'فيش وبرايز', 'price' => '٨٥ ج.م', 'old' => '١١٠ ج.م', 'tag' => '-23%', 'tone' => '#243044', 'points' => 80],
                    ['id' => 3, 'name' => 'مفتاح إنارة ثنائي', 'cat' => 'مفاتيح', 'price' => '٣٥ ج.م', 'tag' => null, 'tone' => '#1a2332', 'points' => 40],
                    ['id' => 4, 'name' => 'سلك كهرباء ٣×٢٫٥ مم', 'cat' => 'كابلات وأسلاك', 'price' => '٢٢٠ ج.م', 'tag' => 'جديد', 'tone' => '#222b3b', 'points' => 120],
                ], 0),
            ])
        </div>
    </div>
</section>

<section class="mq-section" style="padding-top:0">
    <div class="mq-container">
        <div class="mq-panel mq-home-loyalty">
            <div>
                <span class="mq-eyebrow">برنامج الولاء</span>
                <h2 style="margin:.4rem 0 .55rem">اشترِ… امسح… اجمع نقاطك</h2>
                <p style="margin:0;color:var(--mq-muted);max-width:520px">كل منتج يحتوي كود QR. امسحه من تطبيق مقام مع كود التاجر لترقى بين الفضي والذهبي والبلاتينيوم وتدخل عجلة الحظ.</p>
            </div>
            <div class="mq-hero-actions">
                <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-primary">تفاصيل الولاء</a>
                <a href="{{ route('store.profile') }}" class="mq-btn mq-btn-ghost">محفظتي</a>
            </div>
        </div>
    </div>
</section>
@endsection
