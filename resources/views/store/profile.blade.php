@extends('store.layouts.app')

@section('title', 'حسابي | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>حسابي</span>
        </div>
        <h1 class="mq-page-title">حسابي</h1>
        <p class="mq-page-lead">إدارة الطلبات، محفظة النقاط، ومستوى الولاء.</p>

        <div class="mq-profile-layout">
            <aside class="mq-profile-side">
                <div class="mq-panel mq-profile-card">
                    <div class="mq-profile-avatar">أ م</div>
                    <h2>أحمد محمد</h2>
                    <p>٠١٠١٢٣٤٥٦٧٨</p>
                    <span class="mq-rank-badge">رتبة ذهبية</span>
                    <div class="mq-profile-nav">
                        <a href="#wallet" class="is-active">المحفظة والرتبة</a>
                        <a href="#orders">طلباتي</a>
                        <a href="#tx">حركات النقاط</a>
                        <a href="#settings">بيانات الحساب</a>
                        <a href="{{ route('store.loyalty') }}">نظام الولاء</a>
                    </div>
                </div>
            </aside>

            <div class="mq-profile-main">
                <div class="mq-panel" id="wallet">
                    <h3 class="mq-side-title">محفظة الولاء</h3>
                    <div class="mq-wallet-grid">
                        <div class="mq-wallet-stat">
                            <span>رصيد النقاط</span>
                            <strong>٢٬٤٥٠</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>الرتبة الحالية</span>
                            <strong>ذهبي</strong>
                        </div>
                        <div class="mq-wallet-stat">
                            <span>للوصول إلى بلاتينيوم</span>
                            <strong>٥٥٠ نقطة</strong>
                        </div>
                    </div>
                    <div class="mq-progress">
                        <div class="mq-progress-bar" style="width:78%"></div>
                    </div>
                    <p class="mq-muted-note">امسح كود QR داخل المنتج عبر تطبيق مقام لإضافة نقاط إلى محفظتك.</p>
                    <a href="{{ route('store.loyalty') }}" class="mq-btn mq-btn-ghost">كيف يعمل نظام الولاء؟</a>
                </div>

                <div class="mq-panel" id="orders">
                    <h3 class="mq-side-title">آخر الطلبات</h3>
                    <div class="mq-order-list">
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1042</strong>
                                <span>بريزة جدارية + فيشة متعددة</span>
                            </div>
                            <em class="mq-status is-shipped">تم الشحن</em>
                            <span>٣٢٥ ج.م</span>
                        </div>
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1031</strong>
                                <span>قاطع كهرباء ٣٢ أمبير</span>
                            </div>
                            <em class="mq-status is-done">تم التسليم</em>
                            <span>٩٥ ج.م</span>
                        </div>
                        <div class="mq-order-row">
                            <div>
                                <strong>#MQ-1018</strong>
                                <span>سلك كهرباء ٣×٢٫٥ مم</span>
                            </div>
                            <em class="mq-status is-prep">جاري التجهيز</em>
                            <span>٢٢٠ ج.م</span>
                        </div>
                    </div>
                </div>

                <div class="mq-panel" id="tx">
                    <h3 class="mq-side-title">حركات النقاط</h3>
                    <div class="mq-tx-list">
                        <div class="mq-tx-row"><span>مسح كود QR — بريزة جدارية</span><strong class="up">+٥٠</strong></div>
                        <div class="mq-tx-row"><span>مسح كود QR — سلك كهرباء</span><strong class="up">+١٢٠</strong></div>
                        <div class="mq-tx-row"><span>محاولة عجلة الحظ</span><strong class="down">−٣٠</strong></div>
                        <div class="mq-tx-row"><span>فوز بكوبون خصم ١٠٪</span><strong class="up">كوبون</strong></div>
                    </div>
                </div>

                <div class="mq-panel" id="settings">
                    <h3 class="mq-side-title">بيانات الحساب</h3>
                    <form class="mq-profile-form" onsubmit="return false;">
                        <div class="mq-field">
                            <label>الاسم الكامل</label>
                            <input type="text" value="أحمد محمد">
                        </div>
                        <div class="mq-field">
                            <label>رقم الجوال</label>
                            <input type="tel" value="01012345678" dir="ltr">
                        </div>
                        <div class="mq-field">
                            <label>المدينة</label>
                            <input type="text" value="القاهرة">
                        </div>
                        <button type="submit" class="mq-btn mq-btn-primary">حفظ التعديلات</button>
                    </form>
                    <div class="mq-merchant-cta">
                        <strong>هل أنت تاجر؟</strong>
                        <p>اطلب التحويل لحساب تاجر من التطبيق للحصول على كود تاجر ومتابعة مسح العملاء.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
