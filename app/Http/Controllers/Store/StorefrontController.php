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
            'title' => __('store.legal.privacy_title'),
            'lead' => __('store.legal.privacy_lead'),
            'body' => $this->legalBody('privacy'),
        ]);
    }

    public function terms(): View
    {
        return view('store.legal', [
            'title' => __('store.legal.terms_title'),
            'lead' => __('store.legal.terms_lead'),
            'body' => $this->legalBody('terms'),
        ]);
    }

    public function shipping(): View
    {
        return view('store.legal', [
            'title' => __('store.legal.shipping_title'),
            'lead' => __('store.legal.shipping_lead'),
            'body' => $this->legalBody('shipping'),
        ]);
    }

    public function returns(): View
    {
        return view('store.legal', [
            'title' => __('store.legal.returns_title'),
            'lead' => __('store.legal.returns_lead'),
            'body' => $this->legalBody('returns'),
        ]);
    }

    private function legalBody(string $page): string
    {
        $locale = app()->getLocale();

        $bodies = [
            'privacy' => [
                'ar' => <<<'HTML'
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
                'en' => <<<'HTML'
                    <p>MAQAM respects your privacy. When you use the store or app we may collect your phone number, order details, delivery address, and QR scan / loyalty history to operate the service.</p>
                    <h3>What data do we collect?</h3>
                    <ul>
                        <li>Account data: name, mobile number, and city.</li>
                        <li>Order and payment data (without storing full card details on our servers when paying via Paymob).</li>
                        <li>Points, scan history, and approximate location when needed to prevent fraud.</li>
                    </ul>
                    <h3>Why do we use the data?</h3>
                    <ul>
                        <li>Fulfilling orders and customer support.</li>
                        <li>Running the loyalty wallet, ranks, and lucky wheel.</li>
                        <li>Protecting the system from code misuse.</li>
                        <li>Sending order and offer notifications when you agree.</li>
                    </ul>
                    <h3>Data sharing</h3>
                    <p>We do not sell your data. We may share it with shipping, payment, and messaging providers only as needed to deliver the service.</p>
                    <h3>Your rights</h3>
                    <p>You may request corrections or ask questions via the Contact page or in-app support.</p>
                HTML,
            ],
            'terms' => [
                'ar' => <<<'HTML'
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
                'en' => <<<'HTML'
                    <p>By using the MAQAM website or app you agree to these terms for buying electrical supplies and using the loyalty program.</p>
                    <h3>Products and orders</h3>
                    <ul>
                        <li>Prices are in Egyptian pounds and may change without notice until the order is confirmed.</li>
                        <li>Checkout requires WhatsApp OTP verification at payment.</li>
                        <li>Order statuses: new, preparing, shipped, delivered — with cancellation/refunds per policy.</li>
                    </ul>
                    <h3>QR codes and loyalty</h3>
                    <ul>
                        <li>Each code may be used only once.</li>
                        <li>Points are calculated from the prize category linked to the code.</li>
                        <li>Fraud or misuse may result in account suspension.</li>
                    </ul>
                    <h3>Liability</h3>
                    <p>Customers must use electrical products safely according to instructions and local standards.</p>
                HTML,
            ],
            'shipping' => [
                'ar' => <<<'HTML'
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
                'en' => <<<'HTML'
                    <p>We deliver electrical supply orders across most governorates through trusted shipping partners.</p>
                    <h3>Delivery timelines</h3>
                    <ul>
                        <li>Cairo &amp; Giza: usually 1–3 business days after confirmation.</li>
                        <li>Other governorates: usually 2–5 business days depending on the area.</li>
                    </ul>
                    <h3>Shipping cost</h3>
                    <p>Calculated at checkout by weight and area, and may be free during selected offers.</p>
                    <h3>Order tracking</h3>
                    <p>You can track shipping from My account after the order status updates to “Shipped”.</p>
                HTML,
            ],
            'returns' => [
                'ar' => <<<'HTML'
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
                'en' => <<<'HTML'
                    <p>We aim to deliver products in good condition. If an item arrives damaged or incorrect, you may request an exchange/return within 7 days of receipt.</p>
                    <h3>Acceptance conditions</h3>
                    <ul>
                        <li>Product in original packaging where possible, with proof of order.</li>
                        <li>If the QR code was scratched and scanned, points status is reviewed before completing the return.</li>
                        <li>Defects from improper install or misuse are not covered.</li>
                    </ul>
                    <h3>How to request</h3>
                    <p>Contact us via WhatsApp or the Contact page with your order number and clear product photos.</p>
                HTML,
            ],
        ];

        return $bodies[$page][$locale] ?? $bodies[$page]['ar'];
    }
}
