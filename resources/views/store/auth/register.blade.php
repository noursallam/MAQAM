@extends('store.layouts.app')

@section('title', 'إنشاء حساب | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-auth">
            <h1 class="mq-page-title" style="text-align:center">إنشاء حساب</h1>
            <p class="mq-page-lead" style="text-align:center">أنشئ حسابًا جديدًا في متجر مقام.</p>
            <form class="mq-panel" onsubmit="return false;">
                <div class="mq-field">
                    <label>الاسم الكامل</label>
                    <input type="text" placeholder="اسمك">
                </div>
                <div class="mq-field">
                    <label>البريد الإلكتروني</label>
                    <input type="email" placeholder="name@email.com" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>كلمة المرور</label>
                    <input type="password" placeholder="••••••••">
                </div>
                <button type="submit" class="mq-btn mq-btn-primary mq-btn-block">إنشاء الحساب</button>
                <div class="mq-auth-alt">
                    لديك حساب؟ <a href="{{ route('store.login') }}">تسجيل الدخول</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
