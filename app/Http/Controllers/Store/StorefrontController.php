<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rank;
use App\Models\SystemSetting;
use App\Models\WheelPrize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $banners = Banner::active()->orderBy('sort_order')->get();

        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('id')
            ->get();

        $featuredProducts = Product::with(['category', 'thumbnail', 'images'])
            ->where('is_active', true)
            ->latest()
            ->take(8)
            ->get();

        $ranks = Rank::where('is_active', true)
            ->orderBy('min_points', 'asc')
            ->get();

        return view('store.home', compact('banners', 'categories', 'featuredProducts', 'ranks'));
    }

    public function shop(Request $request): View
    {
        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('id')
            ->get();

        $query = Product::with(['category', 'thumbnail', 'images'])
            ->where('is_active', true);

        // Filter by Category
        $activeCategoryId = null;
        if ($request->filled('category')) {
            $catParam = $request->input('category');
            if (is_numeric($catParam)) {
                $query->where('category_id', (int) $catParam);
                $activeCategoryId = (int) $catParam;
            } else {
                $cat = Category::where('slug', $catParam)->first();
                if ($cat) {
                    $query->where('category_id', $cat->id);
                    $activeCategoryId = $cat->id;
                }
            }
        }

        // Search Keyword
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description_ar', 'like', "%{$term}%");
            });
        }

        // Price Filtering
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        // Quick Price Radio Filters
        if ($request->input('price_range') === 'under_200') {
            $query->where('price', '<', 200);
        } elseif ($request->input('price_range') === 'between_200_500') {
            $query->whereBetween('price', [200, 500]);
        } elseif ($request->input('price_range') === 'over_500') {
            $query->where('price', '>', 500);
        }

        // Availability Filtering
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name_ar', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $minPrice = (float) (Product::where('is_active', true)->min('price') ?: 0);
        $maxPrice = (float) (Product::where('is_active', true)->max('price') ?: 500);

        $recommendedProducts = Product::with(['category', 'thumbnail', 'images'])
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->latest()
            ->take(4)
            ->get();

        return view('store.shop', compact(
            'products',
            'categories',
            'activeCategoryId',
            'minPrice',
            'maxPrice',
            'recommendedProducts'
        ));
    }

    public function product(Product|string|int $product): View
    {
        if (! ($product instanceof Product)) {
            $product = Product::with(['category', 'images', 'colors', 'options'])
                ->where('is_active', true)
                ->where('id', $product)
                ->firstOrFail();
        } else {
            $product->loadMissing(['category', 'images', 'colors', 'options']);
        }

        $relatedProducts = Product::with(['category', 'thumbnail', 'images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('store.product', compact('product', 'relatedProducts'));
    }

    public function loyalty(): View
    {
        $ranks = Rank::where('is_active', true)
            ->orderBy('min_points', 'asc')
            ->get();

        $wheelPrizes = WheelPrize::where('is_active', true)
            ->orderBy('probability', 'desc')
            ->get();

        $wheelEnabled = (bool) SystemSetting::getValue('wheel_enabled', true);

        return view('store.loyalty', compact('ranks', 'wheelPrizes', 'wheelEnabled'));
    }

    public function contact(): View
    {
        $contact = [
            'phone' => SystemSetting::getValue('store_phone', '01001234567'),
            'whatsapp' => SystemSetting::getValue('store_whatsapp', '201001234567'),
            'email' => SystemSetting::getValue('support_email', 'support@maqam-eg.com'),
            'address' => SystemSetting::getValue('store_address', 'القاهرة، جمهورية مصر العربية'),
        ];

        return view('store.contact', compact('contact'));
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Create an admin notification for incoming contact form
        AppNotification::create([
            'user_id' => null,
            'title' => "رسالة تواصل جديدة من: {$validated['name']} ({$validated['subject']})",
            'body' => "البريد: {$validated['email']}\n\nالرسالة:\n{$validated['message']}",
            'type' => 'contact_inquiry',
            'is_read' => false,
        ]);

        return back()->with('success', __('store.contact.sent_success'));
    }

    public function about(): View
    {
        return view('store.about');
    }

    public function blog(): View
    {
        return view('store.blog');
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
