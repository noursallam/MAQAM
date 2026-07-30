@extends('store.layouts.app')

@section('title', 'سلة المشتريات | مقام')

@section('content')
<section class="mq-page">
    <div class="mq-container">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">الرئيسية</a>
            <span class="sep">/</span>
            <span>السلة</span>
        </div>
        <h1 class="mq-page-title">سلة المشتريات</h1>
        <p class="mq-page-lead">راجع منتجاتك قبل إتمام الشراء — النقاط تُضاف بعد مسح QR من التطبيق.</p>

        <div class="mq-cart-layout">
            <div class="mq-panel" style="overflow:auto">
                <table class="mq-table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>الكمية</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="mq-cart-item">
                                    <div class="mq-cart-thumb">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.4"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                                    </div>
                                    <div>
                                        <strong>بريزة جدارية ثلاثية</strong>
                                        <div style="color:var(--mq-muted);font-size:.85rem">فيش وبرايز · +٥٠ نقطة</div>
                                    </div>
                                </div>
                            </td>
                            <td>٤٥ ج.م</td>
                            <td>
                                <div class="mq-qty">
                                    <button type="button">−</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>
                            </td>
                            <td>٤٥ ج.م</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="mq-cart-item">
                                    <div class="mq-cart-thumb">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c5a059" stroke-width="1.4"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                                    </div>
                                    <div>
                                        <strong>فيشة متعددة المنافذ</strong>
                                        <div style="color:var(--mq-muted);font-size:.85rem">فيش وبرايز · +٨٠ نقطة</div>
                                    </div>
                                </div>
                            </td>
                            <td>٨٥ ج.م</td>
                            <td>
                                <div class="mq-qty">
                                    <button type="button">−</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>
                            </td>
                            <td>٨٥ ج.م</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <aside class="mq-panel">
                <h3 class="mq-side-title">ملخص الطلب</h3>
                <div class="mq-summary-row"><span>المجموع الفرعي</span><span>١٣٠ ج.م</span></div>
                <div class="mq-summary-row"><span>الشحن</span><span>٣٥ ج.م</span></div>
                <div class="mq-summary-row total"><span>الإجمالي</span><span>١٦٥ ج.م</span></div>
                <a href="{{ route('store.checkout') }}" class="mq-btn mq-btn-primary mq-btn-block" style="margin-top:1rem">إتمام الشراء</a>
                <a href="{{ route('store.shop') }}" class="mq-btn mq-btn-ghost mq-btn-block" style="margin-top:.6rem">متابعة التسوق</a>
            </aside>
        </div>
    </div>
</section>
@endsection
