@extends('store.layouts.app')

@section('title', 'من نحن | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>من نحن</span>
        </div>
        <h1 class="mq-page-title">من نحن</h1>
        <p class="mq-page-lead">مقام منظومة متكاملة لبيع الأدوات الكهربائية مع برنامج ولاء ومكافآت عبر التطبيق والمتجر.</p>

        <div class="mq-about-grid">
            <div class="mq-panel mq-prose">
                <h3>غرض البرنامج</h3>
                <p>نربط بين شراء المستلزمات الكهربائية (فيش، برايز، مفاتيح، كابلات…) وبين تجربة ولاء حقيقية: كل منتج يحمل كود QR، تمسحه من التطبيق مع كود التاجر لتجمع نقاطًا وترقى بين الرتب.</p>
                <div class="mq-stat-grid">
                    <div class="mq-stat"><strong>QR</strong><span>مسح مرة واحدة</span></div>
                    <div class="mq-stat"><strong>٣ رتب</strong><span>فضي / ذهبي / بلاتينيوم</span></div>
                    <div class="mq-stat"><strong>عجلة</strong><span>حظ ومكافآت</span></div>
                </div>
            </div>
            <div class="mq-panel mq-prose">
                <h3>ماذا نقدّم؟</h3>
                <ul>
                    <li>متجر إلكتروني لتصفح وشراء المنتجات دون حساب إلزامي حتى الدفع.</li>
                    <li>تطبيق موبايل لمسح الأكواد، المحفظة، الرتب، وعجلة الحظ.</li>
                    <li>دعم التجار بكود تاجر وتتبع عمليات المسح.</li>
                    <li>شحن ومتابعة طلبات مع خيارات دفع متعددة.</li>
                </ul>
                <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-primary">اعرف أكثر عن الولاء</a>
            </div>
        </div>
    </div>
</section>
@endsection
