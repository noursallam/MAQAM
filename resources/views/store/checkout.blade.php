@extends('store.layouts.app')

@section('title', 'إتمام الشراء | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <a href="{{ route('store.cart') }}">السلة</a>
            <span class="sep">/</span>
            <span>إتمام الشراء</span>
        </div>
        <h1 class="mq-page-title">إتمام الشراء</h1>
        <p class="mq-page-lead">أكمل بيانات التوصيل والدفع.</p>

        <div class="mq-checkout-layout">
            <form class="mq-panel" onsubmit="return false;">
                <h3 class="mq-side-title">بيانات التوصيل</h3>
                <div class="mq-field">
                    <label>الاسم الكامل</label>
                    <input type="text" placeholder="اكتب اسمك">
                </div>
                <div class="mq-field">
                    <label>رقم الجوال</label>
                    <input type="tel" placeholder="01xxxxxxxxx" dir="ltr">
                </div>
                <div class="mq-field">
                    <label>المدينة</label>
                    <select>
                        <option>القاهرة</option>
                        <option>الجيزة</option>
                        <option>الإسكندرية</option>
                        <option>المنصورة</option>
                    </select>
                </div>
                <div class="mq-field">
                    <label>العنوان</label>
                    <textarea placeholder="الحي، الشارع، رقم المبنى"></textarea>
                </div>
                <h3 class="mq-side-title">طريقة الدفع</h3>
                <div class="mq-filter-group">
                    <label><input type="radio" name="pay" checked> الدفع عند الاستلام</label>
                    <label><input type="radio" name="pay"> بطاقة / محفظة إلكترونية (Paymob)</label>
                    <label><input type="radio" name="pay"> محفظة مقام</label>
                </div>
                <button type="submit" class="mq-btn mq-btn-primary" style="margin-top:1rem">تأكيد الطلب</button>
            </form>

            <aside class="mq-panel">
                <h3 class="mq-side-title">ملخص الطلب</h3>
                <div class="mq-summary-row"><span>منتجان</span><span>١٣٠ ج.م</span></div>
                <div class="mq-summary-row"><span>الشحن</span><span>٣٥ ج.م</span></div>
                <div class="mq-summary-row total"><span>الإجمالي</span><span>١٦٥ ج.م</span></div>
            </aside>
        </div>
    </div>
</section>
@endsection
