@extends('store.layouts.app')

@section('title', 'تسجيل الدخول | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">تسجيل الدخول</h1>
            <p class="mq-page-lead" style="text-align:center">ادخل برقم الجوال عبر واتساب OTP لإدارة طلباتك ومحفظة النقاط.</p>
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>رقم الجوال</label>
                    <input type="tel" placeholder="01xxxxxxxxx" dir="ltr">
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">إرسال كود التحقق</button>
                <div class="mq-auth-alt">
                    ليس لديك حساب؟ <a href="{{ route('store.register') }}">إنشاء حساب</a>
                    · <a href="{{ route('store.profile') }}">معاينة الحساب</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
