<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'متجر مقام')</title>
    <meta name="description" content="@yield('meta_description', 'متجر مقام — تسوق إلكتروني احترافي')">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('maqam_store_theme');
                var theme = saved || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <link rel="icon" type="image/png" href="{{ asset('identity/MAQAM-03.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('store/css/app.css') }}">
    @stack('styles')
</head>
<body class="mq-store">
    <div class="mq-announce">
        <div class="mq-container mq-announce-inner">
            <span>أدوات كهربائية موثوقة للتركيب والاستخدام</span>
            <span>نقاط ولاء عبر مسح QR داخل المنتج</span>
            <span>شحن سريع لجميع المحافظات</span>
        </div>
    </div>

    <header class="mq-header">
        <div class="mq-container mq-header-row">
            <a class="mq-logo" href="{{ route('store.home') }}" aria-label="مقام">
                <img src="{{ asset('identity/MAQAM-logo-header.png') }}" alt="MAQAM">
            </a>

            <nav class="mq-nav" id="mqNav" aria-label="القائمة الرئيسية">
                <a href="{{ route('store.home') }}" class="{{ request()->routeIs('store.home') ? 'is-active' : '' }}">الرئيسية</a>
                <a href="{{ route('store.shop') }}" class="{{ request()->routeIs('store.shop*') || request()->routeIs('store.product') ? 'is-active' : '' }}">المتجر</a>
                <a href="{{ route('store.loyalty') }}" class="{{ request()->routeIs('store.loyalty') ? 'is-active' : '' }}">الولاء</a>
                <a href="{{ route('store.about') }}" class="{{ request()->routeIs('store.about') ? 'is-active' : '' }}">من نحن</a>
                <a href="{{ route('store.faq') }}" class="{{ request()->routeIs('store.faq') ? 'is-active' : '' }}">الأسئلة</a>
                <a href="{{ route('store.contact') }}" class="{{ request()->routeIs('store.contact') ? 'is-active' : '' }}">تواصل</a>
            </nav>

            <div class="mq-header-actions">
                <button type="button" class="mq-icon-btn mq-theme-toggle" data-mq-theme-toggle aria-label="تبديل الوضع">
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>
                </button>
                <button type="button" class="mq-icon-btn" aria-label="بحث">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>
                <a href="{{ route('store.cart') }}" class="mq-icon-btn mq-cart-wrap" aria-label="السلة">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    <span class="mq-badge">2</span>
                </a>
                <a href="{{ route('store.profile') }}" class="mq-login-link">حسابي</a>
                <button type="button" class="mq-icon-btn mq-menu-toggle" id="mqMenuToggle" aria-label="فتح القائمة" aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="mq-footer">
        <div class="mq-container">
            <div class="mq-footer-grid">
                <div>
                    <a class="mq-logo" href="{{ route('store.home') }}">
                        <img src="{{ asset('identity/MAQAM-logo-header.png') }}" alt="MAQAM">
                    </a>
                    <p style="margin-top:1rem">مقام — متجر أدوات كهربائية مع نظام ولاء ونقاط عبر مسح QR.</p>
                </div>
                <div>
                    <h3>المتجر</h3>
                    <ul>
                        <li><a href="{{ route('store.shop') }}">كل المنتجات</a></li>
                        <li><a href="{{ route('store.loyalty') }}">نظام الولاء</a></li>
                        <li><a href="{{ route('store.faq') }}">الأسئلة الشائعة</a></li>
                        <li><a href="{{ route('store.about') }}">من نحن</a></li>
                    </ul>
                </div>
                <div>
                    <h3>حسابي</h3>
                    <ul>
                        <li><a href="{{ route('store.profile') }}">الملف الشخصي</a></li>
                        <li><a href="{{ route('store.login') }}">تسجيل الدخول</a></li>
                        <li><a href="{{ route('store.cart') }}">السلة</a></li>
                        <li><a href="{{ route('store.checkout') }}">إتمام الشراء</a></li>
                    </ul>
                </div>
                <div>
                    <h3>السياسات</h3>
                    <ul>
                        <li><a href="{{ route('store.privacy') }}">سياسة الخصوصية</a></li>
                        <li><a href="{{ route('store.terms') }}">الشروط والأحكام</a></li>
                        <li><a href="{{ route('store.shipping') }}">سياسة الشحن</a></li>
                        <li><a href="{{ route('store.returns') }}">الاستبدال والاسترجاع</a></li>
                    </ul>
                </div>
            </div>
            <div class="mq-footer-bottom">
                <span>© {{ date('Y') }} مقام. جميع الحقوق محفوظة.</span>
                <span><a href="{{ route('store.contact') }}">تواصل معنا</a></span>
            </div>
        </div>
    </footer>

    <div class="mq-float" aria-label="تواصل سريع">
        <a class="mq-float-call" href="tel:+1001234567890" aria-label="اتصال">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1L6.6 10.8z"/></svg>
        </a>
        <a class="mq-float-wa" href="https://wa.me/1001234567890" target="_blank" rel="noopener" aria-label="واتساب">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.15 6.4 2.15 11.84c0 1.96.52 3.8 1.44 5.4L2 22l4.92-1.55a9.86 9.86 0 0 0 5.12 1.4h.01c5.46 0 9.89-4.4 9.89-9.84S17.5 2 12.04 2zm5.74 14.1c-.24.68-1.4 1.24-1.93 1.32-.5.07-1.12.1-1.81-.11-.42-.13-.95-.31-1.64-.61-2.88-1.25-4.76-4.15-4.9-4.34-.14-.2-1.16-1.54-1.16-2.94s.73-2.08 1-2.51c.24-.4.54-.5.72-.5h.52c.17 0 .4-.06.62.47.24.58.8 2 .87 2.14.07.14.12.3.02.48-.1.2-.15.3-.3.47-.14.16-.3.36-.43.48-.14.14-.29.29-.12.56.17.28.74 1.22 1.6 1.98 1.1.97 2.02 1.27 2.3 1.41.29.14.45.12.62-.07.17-.2.72-.84.91-1.13.2-.28.39-.24.65-.14.27.1 1.7.8 2 .95.29.14.48.22.55.34.07.13.07.74-.17 1.42z"/></svg>
        </a>
    </div>

    <script src="{{ asset('store/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
