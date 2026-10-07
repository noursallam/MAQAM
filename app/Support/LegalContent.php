<?php

namespace App\Support;

/**
 * The legal pages (privacy, terms, shipping, returns), shared by the website and the mobile API.
 */
class LegalContent
{
    public const PAGES = ['privacy', 'terms', 'shipping', 'returns'];

    public static function body(string $page): string
    {
        $locale = app()->getLocale();

        $bodies = [
            'privacy' => [
                'ar' => <<<'HTML'
                    <p>تحترم مقام خصوصيتك. عند استخدام المتجر أو التطبيق قد نجمع رقم الهاتف، بيانات الطلب، وعنوان التوصيل، وسجل مسح أكواد QR ونقاط الولاء لتشغيل الخدمة.</p>
                    <h3>ما البيانات التي نجمعها؟</h3>
                    <ul>
                        <li>بيانات الحساب: الاسم ورقم الجوال والمدينة.</li>
                        <li>بيانات الطلبات والدفع (معالجة مشفرة وآمنة عبر بوابة Kashier).</li>
                        <li>سجل النقاط والمسح والموقع التقريبي عند الحاجة لمكافحة الاحتيال.</li>
                    </ul>
                    <h3>لماذا نستخدم البيانات؟</h3>
                    <ul>
                        <li>تنفيذ الطلبات وخدمة العملاء.</li>
                        <li>تشغيل محفظة الولاء والرتب وعجلة الحظ.</li>
                        <li>حماية النظام من إساءة استخدام الأكواد.</li>
                    </ul>
                HTML,
                'en' => <<<'HTML'
                    <p>MAQAM respects your privacy. When using our store or app we collect data necessary to fulfill your orders and operate the loyalty rewards program.</p>
                HTML,
            ],
            'terms' => [
                'ar' => <<<'HTML'
                    <p>باستخدامك لموقع أو متجر مقام فأنت توافق على هذه الشروط المتعلقة بشراء الأدوات الكهربائية ونظام الولاء.</p>
                    <h3>المنتجات والطلبات</h3>
                    <ul>
                        <li>الأسعار بالجنيه المصري شاملة أو مضافاً إليها مصاريف الشحن حسب العنوان.</li>
                        <li>طرق الدفع المتاحة: الدفع الإلكتروني عبر بوابة Kashier (بطاقات بنكية، محافظ إلكترونية، ميزة)، أو الدفع عند الاستلام (COD)، أو نقاط محفظة الولاء.</li>
                    </ul>
                HTML,
                'en' => <<<'HTML'
                    <p>By using the MAQAM store you agree to our terms for purchasing electrical supplies and loyalty rewards program.</p>
                HTML,
            ],
            'shipping' => [
                'ar' => <<<'HTML'
                    <p>نوصل طلبات الأدوات الكهربائية إلى جميع محافظات مصر عبر شركاء شحن معتمدين.</p>
                    <h3>مدة التوصيل</h3>
                    <ul>
                        <li>القاهرة والجيزة: عادة خلال ١–٣ أيام عمل بعد التأكيد.</li>
                        <li>باقي المحافظات: عادة خلال ٢–٥ أيام عمل.</li>
                    </ul>
                HTML,
                'en' => <<<'HTML'
                    <p>We deliver electrical supply orders across Egypt through trusted courier partners.</p>
                HTML,
            ],
            'returns' => [
                'ar' => <<<'HTML'
                    <p>يمكنك طلب الاستبدال أو الاسترجاع خلال ١٤ يوماً من استلام الطلب وفق قانون حماية المستهلك للمنتجات بحالتها الأصلية.</p>
                HTML,
                'en' => <<<'HTML'
                    <p>Returns and exchanges are accepted within 14 days of receipt for items in original condition.</p>
                HTML,
            ],
        ];

        return $bodies[$page][$locale] ?? $bodies[$page]['ar'];
    }
}
