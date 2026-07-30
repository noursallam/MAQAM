@extends('store.layouts.app')

@section('title', 'نظام الولاء | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>نظام الولاء</span>
        </div>
        <h1 class="mq-page-title">نظام الولاء والمكافآت</h1>
        <p class="mq-page-lead">اشترِ من متجر مقام، امسح كود QR داخل المنتج عبر التطبيق، واجمع نقاطًا للترقية والجوائز.</p>

        <div class="mq-loyalty-steps">
            <div class="mq-panel">
                <span class="mq-step-num">١</span>
                <h3>اشتري منتجًا كهربائيًا</h3>
                <p>فيش، برايز، مفاتيح، كابلات وأدوات — وكل منتج يحتوي كود QR مغطى بطبقة قشط.</p>
            </div>
            <div class="mq-panel">
                <span class="mq-step-num">٢</span>
                <h3>امسح الكود من التطبيق</h3>
                <p>افتح تطبيق مقام، امسح الكود، وأدخل/اختر كود التاجر لإضافة النقاط فورًا أو لاحقًا دون إنترنت.</p>
            </div>
            <div class="mq-panel">
                <span class="mq-step-num">٣</span>
                <h3>ارتقِ واربح</h3>
                <p>ترتقي بين الرتب فضي / ذهبي / بلاتينيوم، وتستخدم النقاط في عجلة الحظ والكوبونات والمزايا.</p>
            </div>
        </div>

        <div class="mq-about-grid" style="margin-top:1.25rem">
            <div class="mq-panel mq-prose">
                <h3>الرتب</h3>
                <ul>
                    <li><strong>فضي:</strong> البداية بعد أول عمليات مسح ناجحة.</li>
                    <li><strong>ذهبي:</strong> مزايا أفضل في عجلة الحظ ونسب الفوز.</li>
                    <li><strong>بلاتينيوم:</strong> أعلى مستوى ولاء مع أولوية في العروض.</li>
                </ul>
                <h3>عجلة الحظ</h3>
                <p>تُخصم نقاط حسب رتبتك مقابل كل محاولة، والجوائز قد تكون نقاطًا إضافية أو خصمًا أو كوبونًا.</p>
            </div>
            <div class="mq-panel mq-prose">
                <h3>ملاحظات مهمة</h3>
                <ul>
                    <li>كل كود QR يُستخدم مرة واحدة فقط.</li>
                    <li>النقاط تُحسب من فئة الهدايا المرتبطة بالكود، وليست من سعر المنتج مباشرة.</li>
                    <li>المحفظة تظهر في التطبيق وفي صفحة حسابك على الموقع.</li>
                    <li>الدفع عند الاستلام أو إلكترونيًا أو من المحفظة حسب المتاح عند الطلب.</li>
                </ul>
                <a href="{{ route('store.profile') }}" class="mq-btn mq-btn-primary">عرض محفظتي</a>
            </div>
        </div>
    </div>
</section>
@endsection
