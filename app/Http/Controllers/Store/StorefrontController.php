<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('store.home');
    }

    public function shop(): View
    {
        return view('store.shop');
    }

    public function product(int|string $product = 1): View
    {
        return view('store.product', [
            'productId' => $product,
        ]);
    }

    public function cart(): View
    {
        return view('store.cart');
    }

    public function checkout(): View
    {
        return view('store.checkout');
    }

    public function about(): View
    {
        return view('store.about');
    }

    public function contact(): View
    {
        return view('store.contact');
    }

    public function blog(): View
    {
        return view('store.blog');
    }

    public function login(): View
    {
        return view('store.auth.login');
    }

    public function register(): View
    {
        return view('store.auth.register');
    }

    public function profile(): View
    {
        return view('store.profile');
    }

    public function loyalty(): View
    {
        return view('store.loyalty');
    }

    public function faq(): View
    {
        return view('store.faq');
    }

    public function privacy(): View
    {
        return view('store.legal', [
            'title' => 'سياسة الخصوصية',
            'lead' => 'كيف نجمع بياناتك ونستخدمها داخل منظومة مقام.',
            'body' => <<<'HTML'
                <p>تحترم مقام خصوصيتك. عند استخدام المتجر أو التطبيق قد نجمع رقم الهاتف، بيانات الطلب، وعنوان التوصيل، وسجل مسح أكواد QR ونقاط الولاء لتشغيل الخدمة.</p>
                <h3>ما البيانات التي نجمعها؟</h3>
                <ul>
                    <li>بيانات الحساب: الاسم ورقم الجوال والمدينة.</li>
                    <li>بيانات الطلبات والدفع (دون تخزين كامل لبيانات البطاقة على خوادمنا عند الدفع عبر Paymob).</li>
                    <li>سجل النقاط والمسح والموقع التقريبي عند الحاجة لمكافحة الاحتيال.</li>
                </ul>
                <h3>لماذا نستخدم البيانات؟</h3>
                <ul>
                    <li>تنفيذ الطلبات وخدمة العملاء.</li>
                    <li>تشغيل محفظة الولاء والرتب وعجلة الحظ.</li>
                    <li>حماية النظام من إساءة استخدام الأكواد.</li>
                    <li>إرسال إشعارات متعلقة بالطلبات والعروض عند موافقتك.</li>
                </ul>
                <h3>مشاركة البيانات</h3>
                <p>لا نبيع بياناتك. قد نشاركها مع مزودي الشحن والدفع والرسائل بالقدر اللازم لتنفيذ الخدمة فقط.</p>
                <h3>حقوقك</h3>
                <p>يمكنك طلب تصحيح بياناتك أو الاستفسار عبر صفحة تواصل معنا أو الدعم داخل التطبيق.</p>
            HTML,
        ]);
    }

    public function terms(): View
    {
        return view('store.legal', [
            'title' => 'الشروط والأحكام',
            'lead' => 'القواعد العامة لاستخدام متجر وتطبيق مقام.',
            'body' => <<<'HTML'
                <p>باستخدامك لموقع أو تطبيق مقام فأنت توافق على هذه الشروط المتعلقة بشراء الأدوات الكهربائية ونظام الولاء.</p>
                <h3>المنتجات والطلبات</h3>
                <ul>
                    <li>الأسعار بالجنيه المصري وقد تتغير دون إشعار مسبق حتى تأكيد الطلب.</li>
                    <li>إتمام الشراء يتطلب التحقق عبر واتساب OTP عند الدفع.</li>
                    <li>حالات الطلب: جديد، جاري التجهيز، تم الشحن، تم التسليم، مع إمكانية الإلغاء/الاسترداد وفق السياسات.</li>
                </ul>
                <h3>أكواد QR والولاء</h3>
                <ul>
                    <li>كل كود صالح لاستخدام واحد فقط.</li>
                    <li>النقاط تُحسب حسب فئة الهدايا المرتبطة بالكود.</li>
                    <li>أي محاولة احتيال أو إساءة استخدام قد تؤدي لتجميد الحساب.</li>
                </ul>
                <h3>المسؤولية</h3>
                <p>يلتزم العميل بالاستخدام الآمن للمنتجات الكهربائية وفق التعليمات والمعايير المحلية.</p>
            HTML,
        ]);
    }

    public function shipping(): View
    {
        return view('store.legal', [
            'title' => 'سياسة الشحن',
            'lead' => 'مواعيد التوصيل ونطاق التغطية داخل مصر.',
            'body' => <<<'HTML'
                <p>نوصل طلبات الأدوات الكهربائية إلى معظم المحافظات عبر شركاء شحن معتمدين.</p>
                <h3>مدة التوصيل</h3>
                <ul>
                    <li>القاهرة والجيزة: عادة ١–٣ أيام عمل بعد التأكيد.</li>
                    <li>باقي المحافظات: عادة ٢–٥ أيام عمل حسب المنطقة.</li>
                </ul>
                <h3>تكلفة الشحن</h3>
                <p>تُحسب عند إتمام الطلب حسب الوزن والمنطقة، وقد تكون مجانية في العروض المحددة.</p>
                <h3>تتبع الطلب</h3>
                <p>يمكنك متابعة حالة الشحن من صفحة حسابي بعد تحديث حالة الطلب إلى «تم الشحن».</p>
            HTML,
        ]);
    }

    public function returns(): View
    {
        return view('store.legal', [
            'title' => 'الاستبدال والاسترجاع',
            'lead' => 'شروط إعادة المنتجات الكهربائية التالفة أو غير المطابقة.',
            'body' => <<<'HTML'
                <p>نسعى لتسليم منتجات سليمة. إن وصل المنتج تالفًا أو غير مطابق يمكنك طلب الاستبدال/الاسترجاع خلال ٧ أيام من الاستلام.</p>
                <h3>شروط القبول</h3>
                <ul>
                    <li>المنتج في عبوته الأصلية قدر الإمكان مع إثبات الطلب.</li>
                    <li>إن كان كود QR قد قُشط ومُسح، تُراجع حالة النقاط قبل إتمام الاسترجاع.</li>
                    <li>العيوب الناتجة عن سوء التركيب أو الاستخدام غير الصحيح غير مشمولة.</li>
                </ul>
                <h3>طريقة الطلب</h3>
                <p>تواصل عبر واتساب أو صفحة تواصل معنا مع رقم الطلب وصور واضحة للمنتج.</p>
            HTML,
        ]);
    }
}
