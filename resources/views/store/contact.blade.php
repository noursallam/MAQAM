@extends('store.layouts.app')

@section('title', 'تواصل معنا | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>تواصل معنا</span>
        </div>
        <h1 class="mq-page-title">تواصل معنا</h1>
        <p class="mq-page-lead">يسعدنا مساعدتك عبر الهاتف أو واتساب أو البريد.</p>

        <div class="mq-contact-grid">
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>الاسم</label>
                    <input type="text" placeholder="اسمك الكامل">
                </div>
                <div class="mq-field">
                    <label>البريد الإلكتروني</label>
                    <input type="email" placeholder="name@email.com" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>الموضوع</label>
                    <input type="text" placeholder="كيف نقدر نساعدك؟">
                </div>
                <div class="mq-field">
                    <label>الرسالة</label>
                    <textarea placeholder="اكتب رسالتك هنا"></textarea>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary">إرسال الرسالة</button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">بيانات التواصل</h3>
                <p style="color:var(--mq-muted)">الهاتف: <a href="tel:+1001234567890" dir="ltr">+100 123 456 7890</a></p>
                <p style="color:var(--mq-muted)">واتساب: متاح يوميًا من ٩ص إلى ٨م</p>
                <p style="color:var(--mq-muted)">البريد: <a href="mailto:support@maqam.com">support@maqam.com</a></p>
                <a href="https://wa.me/1001234567890" class="mq-btn mq-btn-ghost" style="margin-top:.5rem" target="_blank" rel="noopener">تواصل عبر واتساب</a>
            </aside>
        </div>
    </div>
</section>
@endsection
