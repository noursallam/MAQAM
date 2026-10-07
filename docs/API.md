# MAQAM Mobile API — v1

المرجع المعتمد لمطوّر التطبيق. كل ما هنا منفّذ فعلاً في الخادم ومغطّى باختبارات (`tests/Feature/Api/ApiV1Test.php`).
عند أي تعارض مع `FLUTTER_DEVELOPER_HANDBOOK.md` فهذا الملف هو الصحيح.

> **المرجع التفاعلي الكامل** (كل الحقول والأمثلة وتجربة الطلبات) موجود داخل لوحة التحكم على `/admin/api-docs` ويحتاج حساب مطوّر.

- **Base URL:** `https://maqam-eg.com/api/v1`
- **الصيغة:** JSON فقط، UTF-8
- **التوثيق:** `Authorization: Bearer <token>` (Laravel Sanctum)

---

## 1. القواعد العامة

### الترويسات (Headers)

| الترويسة | إلزامية | الوصف |
|---|---|---|
| `Authorization: Bearer <token>` | في المسارات المحمية | التوكن الراجع من تسجيل الدخول |
| `Content-Type: application/json` | مع أي body | |
| `X-App-Locale` | لا | `ar` (الافتراضي) أو `en` — يحدد لغة الرسائل وحقل `name` |
| `X-Device-Id` | يُفضّل | معرّف ثابت للجهاز، يُسجَّل مع عمليات المسح |

### شكل الرد الناجح

```json
{ "success": true, "message": "…", "data": { } }
```

القوائم المقسّمة لصفحات تضيف `meta`:

```json
{ "success": true, "message": "", "data": [ ], "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 55 } }
```

التقسيم عبر `?page=2&per_page=20` (الحد الأقصى لـ `per_page` هو 50).

### شكل الخطأ (ثابت لكل الأخطاء)

```json
{
  "success": false,
  "message": "رسالة جاهزة للعرض للمستخدم",
  "errors": { "phone": ["…"] },
  "error_code": "VALIDATION_ERROR"
}
```

اعتمد في المنطق على `error_code` وليس على نص `message`.

### أكواد الأخطاء

| HTTP | `error_code` | المعنى |
|---|---|---|
| 401 | `UNAUTHENTICATED` | التوكن مفقود أو منتهٍ أو ملغى ← رجّع المستخدم لتسجيل الدخول |
| 403 | `ACCOUNT_FROZEN_FRAUD` | الحساب موقوف من نظام المخاطر (كل توكناته أُلغيت) |
| 403 | `FORBIDDEN` | غير مسموح |
| 404 | `NOT_FOUND` | العنصر غير موجود أو لا يخص هذا المستخدم |
| 422 | `VALIDATION_ERROR` | بيانات غير صالحة، التفاصيل في `errors` |
| 429 | `TOO_MANY_REQUESTS` | تجاوز حد الطلبات، انتظر (ترويسة `Retry-After`) |
| 500 | `SERVER_ERROR` | خطأ داخلي |
| 410 | `CHALLENGE_EXPIRED` | انتهت جلسة التحقق، ابدأ من جديد |
| 422 | `INVALID_OTP` | كود الدخول خطأ |
| 503 | `WHATSAPP_UNAVAILABLE` | خدمة واتساب غير متاحة حالياً |
| 404 | `QR_NOT_FOUND` | كود QR غير موجود |
| 422 | `QR_ALREADY_USED` / `QR_EXPIRED` | الكود مستخدم / منتهٍ |
| 429 | `SCAN_LOCKED` | إيقاف المسح ساعتين بعد 5 أكواد غير موجودة |
| 422 | `MERCHANT_NOT_FOUND` | كود التاجر غير موجود أو غير معتمد |
| 422 | `MERCHANT_SELF_SCAN` | التاجر يحاول استخدام كوده مع مسحه هو |
| 422 | `INSUFFICIENT_POINTS` | الرصيد لا يكفي (العجلة أو الدفع بالنقاط) |
| 422 | `WHEEL_DISABLED` / `WHEEL_UNAVAILABLE` | العجلة متوقفة |
| 404 | `PRODUCT_UNAVAILABLE` | المنتج غير متاح |
| 422 | `OUT_OF_STOCK` | الكمية غير متوفرة |
| 422 | `INVALID_COUPON` | الكوبون غير صالح |
| 422 | `CART_EMPTY` | السلة فارغة |
| 502 | `PAYMENT_GATEWAY_ERROR` | تعذر بدء الدفع الإلكتروني (لم يُنشأ طلب) |
| 409 | `MERCHANT_ALREADY_EXISTS` | يوجد طلب تاجر مسبق |
| 422 | `ORDER_NOT_CANCELLABLE` | الطلب شُحن أو أُلغي أو مدفوع بالبطاقة |
| 422 | `ADDRESS_NOT_FOUND` / `ADDRESS_LIMIT_REACHED` | عنوان غير موجود / دفتر العناوين ممتلئ (20) |
| 422 | `PHONE_TAKEN` | الرقم الجديد مستخدم |
| 422 | `UPLOAD_FAILED` | تعذر حفظ الملف |
| 503 | `CHATBOT_UNAVAILABLE` | المساعد غير متاح |
| 429 | `CHAT_LIMIT_REACHED` | انتهى الحد اليومي لرسائل المساعد (60) |

### حدود الطلبات (Rate limits)

| المسار | الحد |
|---|---|
| كل الـ API | 90 طلب/دقيقة لكل مستخدم (أو IP للزائر) |
| `auth/whatsapp/start` | 5/دقيقة لكل IP و 5 كل 15 دقيقة لكل رقم |
| `auth/whatsapp/check` | 40/دقيقة |
| `auth/whatsapp/verify` | 10/دقيقة، و5 محاولات خطأ لكل جلسة تحقق |
| `qr/*` | 60/دقيقة، و`qr/sync-batch` 10/دقيقة |
| `wheel/spin` | 20/دقيقة |
| `store/checkout` | 10/دقيقة |

---

## 2. تسجيل الدخول (العميل يراسلنا أولاً على واتساب)

لا توجد كلمة مرور. النظام **لا يرسل أي رسالة** قبل أن يرسل العميل كود التحدي من رقمه.

```
1) start  → التطبيق يعرض الكود ويفتح واتساب
2) العميل يرسل الكود إلى رقمنا
3) check  (كل 4 ثوانٍ) → عند وصول الرسالة يرسل الخادم OTP للعميل على واتساب
4) verify → التوكن
```

### `POST /auth/whatsapp/start` — عام

```json
{ "phone": "01012345678", "full_name": "أحمد محمود" }
```

`phone` رقم مصري (010/011/012/015)، يُقبل أيضاً بصيغة `+20…`. `full_name` اختياري ويُستخدم فقط عند إنشاء حساب جديد.

```json
{
  "success": true,
  "data": {
    "challenge_id": "k3J…48 حرفاً",
    "code": "MAQAM-482913",
    "whatsapp_number": "201000000000",
    "whatsapp_url": "https://wa.me/201000000000?text=MAQAM-482913",
    "expires_in_seconds": 600,
    "poll_interval_seconds": 4
  }
}
```

افتح `whatsapp_url` (الرسالة مكتوبة جاهزة) واحتفظ بـ `challenge_id` في الذاكرة فقط.

### `POST /auth/whatsapp/check` — عام

```json
{ "challenge_id": "…" }
```

```json
{ "success": true, "data": { "otp_sent": true, "otp_expires_in_seconds": 300 } }
```

كرّر الطلب كل `poll_interval_seconds` حتى `otp_sent = true` ثم اعرض حقل إدخال الـ OTP.

### `POST /auth/whatsapp/verify` — عام

```json
{ "challenge_id": "…", "otp": "842109", "device_name": "Pixel 8 – Ahmed", "device_token": "fcm-token (اختياري)" }
```

```json
{
  "success": true,
  "data": {
    "token": "3|Kj89qLmNz…",
    "token_type": "Bearer",
    "expires_at": "2027-01-05T10:00:00+00:00",
    "is_new_user": true,
    "user": { "id": 14, "full_name": "أحمد محمود", "phone_number": "01012345678", "email": "…", "role": "customer", "is_active": true, "preferred_language": "ar", "phone_verified_at": "…" },
    "customer": { "…نفس شكل /wallet…" }
  }
}
```

- الحساب يُنشأ تلقائياً عند أول دخول (`is_new_user`).
- الـ OTP يُستخدم مرة واحدة، صالح 5 دقائق.
- توكن واحد لكل `device_name`: الدخول من نفس الجهاز يلغي توكنه القديم.
- خزّن التوكن في **Flutter Secure Storage** فقط. الدخول بالبصمة يتم محلياً (`local_auth`) لفتح التوكن المخزّن؛ لا يوجد endpoint للبصمة.
- التوكن صالح 90 يوماً. عند `401` أعد تسجيل الدخول.

### `POST /auth/logout` · `POST /auth/logout-all` — محمي

يلغي توكن الجهاز الحالي / كل الأجهزة.

---

## 3. الحساب

### `GET /user/profile` — محمي

```json
{ "data": { "user": { }, "customer": { }, "merchant": null } }
```

### `PUT /user/profile` — محمي

كل الحقول اختيارية: `full_name`, `email`, `preferred_language` (`ar`/`en`), `date_of_birth` (`YYYY-MM-DD`). أي حقل آخر يُتجاهل. يرجّع نفس رد `GET`.

### `POST /notifications/device-token` — محمي

```json
{ "device_token": "fcm-token" }
```

---

## 4. مسح أكواد QR

التاجر يُربط **وقت المسح**: `merchant_code` اختياري في كل طلب.

### `POST /qr/preview` — محمي (لا يغيّر شيئاً)

```json
{ "serial_code": "9812736451209384", "merchant_code": "M-AB12CD34" }
```

```json
{ "data": { "serial_code": "…", "status": "active", "category": "…", "points_customer": 25, "points_merchant": 5, "merchant_name": "معرض الأمل", "balance_now": 350, "balance_after": 375 } }
```

### `POST /qr/scan` — محمي

```json
{ "serial_code": "9812736451209384", "merchant_code": "M-AB12CD34", "latitude": 30.0444, "longitude": 31.2357, "device_id": "9B78D82A" }
```

```json
{ "data": { "scan_id": 892, "points_awarded": 25, "new_balance": 375, "total_earned": 1225, "merchant_credited": "معرض الأمل", "merchant_points": 5, "account_frozen": false } }
```

- أرسل الإحداثيات دائماً إن توفرت.
- `account_frozen = true` معناها أن النظام رصد انتقالاً مستحيلاً بين مسحتين وأوقف الحساب: التوكن أُلغي، انقل المستخدم لشاشة "الحساب موقوف".

### `POST /qr/sync-batch` — محمي (مسحات الأوفلاين)

حتى 200 مسحة في الطلب. كل مسحة تنجح أو تفشل بمفردها.

```json
{
  "device_id": "9B78D82A",
  "scans": [
    { "serial_code": "1111222233334444", "merchant_code": "M-AB12CD34", "scanned_at": "2026-09-22 10:15:30", "latitude": 30.0444, "longitude": 31.2357 },
    { "serial_code": "5555666677778888", "merchant_code": null, "scanned_at": "2026-09-22 10:18:12" }
  ]
}
```

```json
{
  "data": {
    "total_synced": 1, "total_failed": 1, "total_points_gained": 25, "current_balance": 400,
    "results": [
      { "serial_code": "1111222233334444", "status": "success", "points": 25, "error_code": null, "message": null },
      { "serial_code": "5555666677778888", "status": "failed", "points": 0, "error_code": "QR_ALREADY_USED", "message": "…" }
    ]
  }
}
```

قيم `status`:
- `success` — أُضيفت النقاط، احذفها من الطابور المحلي.
- `already_synced` — سبق أن أُضيفت لهذا العميل (إعادة إرسال)، احذفها من الطابور.
- `failed` — لن تنجح بإعادة المحاولة، اعرض `message` واحذفها.

إعادة إرسال نفس الدفعة آمنة ولا تضاعف النقاط. عند فشل الطلب كله (شبكة/500) أعد إرسال الدفعة كما هي.

### `GET /merchants/my-recent` — محمي

آخر 20 تاجراً مسح معهم العميل (للاختيار السريع).

```json
{ "data": [ { "merchant_code": "M-AB12CD34", "business_name": "معرض الأمل", "logo_url": null, "last_scanned_at": "2026-09-20T14:30:00+00:00" } ] }
```

---

## 5. المحفظة والرتب

### `GET /wallet` — محمي

```json
{
  "data": {
    "customer_id": 8, "points_balance": 400, "total_points_earned": 1200, "total_points_spent": 800, "date_of_birth": null,
    "rank": { "id": 1, "name": "فضي", "name_ar": "فضي", "name_en": "Silver", "min_points": 0, "max_points": 999, "merchant_points_per_scan": 5, "wheel_cost_points": 50, "icon_url": null },
    "next_rank": { "id": 2, "name": "ذهبي", "min_points": 1000, "…": "…" },
    "points_left_for_next_rank": 600,
    "progress_percent": 40
  }
}
```

`next_rank = null` و `progress_percent = 100` عند أعلى رتبة.

**الرصيد والرتبة شيئان مختلفان:**
- `points_balance` هو الرصيد القابل للصرف (العجلة، الدفع بالنقاط). ينقص عند الصرف.
- الرتبة و`points_left_for_next_rank` و`progress_percent` محسوبة على `total_points_earned` (إجمالي ما كسبه العميل)، فلا تنزل الرتبة عند صرف النقاط.
- الترقية تلقائية لحظة بلوغ الحد، ويصل معها إشعار `rank_upgrade`.

### `GET /wallet/transactions` — محمي، مقسّم لصفحات

فلتر اختياري `?type=earn|spend|refund|expire|adjust`.

```json
{ "data": [ { "id": 51, "type": "earn", "amount": 25, "balance_after": 400, "description": "مسح QR — …", "date": "…" } ], "meta": { } }
```

`amount` سالب في الخصم.

### `GET /ranks` — عام

قائمة الرتب المفعّلة مرتبة تصاعدياً.

### `GET /customer/rewards` — محمي، مقسّم لصفحات

فلتر اختياري `?status=available|used|expired|revoked`.

```json
{ "data": [ { "id": 12, "code": "WD-9812AXYZ", "type": "discount", "source": "wheel", "status": "available", "amount_type": "percentage", "amount_value": "15.00", "product": null, "expires_at": "…", "used_at": null } ] }
```

`type`: `coupon` | `discount` | `product`.

---

## 6. عجلة الحظ

### `GET /wheel/config` — محمي

```json
{
  "data": {
    "is_enabled": true, "cost_points": 50, "customer_balance": 425, "can_spin": true,
    "slices": [
      { "id": 1, "type": "points", "label": "50 نقطة", "label_ar": "50 نقطة", "label_en": "50 Points" },
      { "id": 2, "type": "discount", "label": "خصم 10%", "label_ar": "…", "label_en": "…" },
      { "id": null, "type": "none", "label": "حظ أوفر", "label_ar": "حظ أوفر", "label_en": "Better luck" }
    ]
  }
}
```

ارسم الشرائح بنفس الترتيب. آخر شريحة دائماً "بدون جائزة".

### `POST /wheel/spin` — محمي (body فارغ)

```json
{
  "message": "مبروك! لقد فزت بجائزة.",
  "data": {
    "spin": { "id": 45, "is_win": true, "prize_type": "points", "prize_label": "50 نقطة", "points_cost": 50, "points_won": 50 },
    "reward": null,
    "new_points_balance": 425,
    "slice_index": 0
  }
}
```

- النتيجة تُحسم في الخادم. أوقف الأنيميشن عند `slices[slice_index]` من آخر `config` جلبته.
- `reward` يُملأ عند الفوز بكوبون/خصم/منتج (نفس شكل `/customer/rewards`).

---

## 7. المتجر (عام بدون تسجيل دخول)

### `GET /store/banners`

```json
{ "data": [ { "id": 1, "title": "…", "title_ar": "…", "title_en": "…", "image_url": "https://…", "link_url": "…", "sort_order": 1 } ] }
```

### `GET /store/categories`

```json
{ "data": [ { "id": 2, "name": "…", "name_ar": "…", "name_en": "…", "slug": "…", "parent_id": null, "image_url": "https://…" } ] }
```

### `GET /store/products` — مقسّم لصفحات

`?category_id=2&search=مفتاح&sort=newest|price_asc|price_desc&page=1&per_page=20`

```json
{ "data": [ { "id": 5, "name": "…", "name_ar": "…", "name_en": "…", "price": "350.00", "min_price": "350.00", "max_price": "420.00", "stock_quantity": 40, "sku": "LCK-092", "image_url": "https://…", "category": { "id": 2, "name": "…" } } ], "meta": { } }
```

`min_price`/`max_price` يختلفان عن `price` عندما تكون للخيارات أسعار خاصة. `stock_quantity = 0` معناها غير متوفر.

### `GET /store/products/{id}`

نفس الحقول + `description`, `description_ar`, `description_en`, `weight`, `dimensions` و:

```json
{
  "images": [ { "id": 1, "url": "https://…", "is_thumbnail": true } ],
  "colors": [ { "id": 3, "name": "أسود", "hex": "#000000" } ],
  "options": [ { "id": 7, "name": "المقاس", "value": "20 سم", "price": "420.00" } ]
}
```

### `GET /settings`

الإعدادات العامة (تكلفة الشحن، حد الشحن المجاني، بيانات الدعم…) كـ `{ "key": "value" }`.

---

## 8. السلة (محمية)

السلة مرتبطة بالحساب، وهي نفس سلة الموقع. سلة الزائر تُحفظ داخل التطبيق ثم تُرسل بعد الدخول عبر `cart/add`.

كل مسارات السلة ترجّع السلة كاملة:

```json
{
  "data": {
    "id": 9,
    "items": [ { "id": 14, "product": { "…ملخص المنتج…" }, "product_option_id": 7, "option_label": "المقاس: 20 سم", "quantity": 2, "unit_price": "420.00", "line_total": "840.00" } ],
    "items_count": 2, "coupon_code": null,
    "subtotal": "840.00", "discount": "0.00", "shipping": "0.00", "total": "840.00", "currency": "EGP"
  }
}
```

| المسار | Body |
|---|---|
| `GET /store/cart` | — |
| `POST /store/cart/add` | `{ "product_id": 5, "quantity": 2, "option_id": 7, "color": "أسود" }` (`quantity` الافتراضي 1، الباقي اختياري) |
| `POST /store/cart/update` | `{ "item_id": 14, "quantity": 3 }` (0 يحذف العنصر) |
| `DELETE /store/cart/remove/{item_id}` | — |
| `POST /store/cart/coupon` | `{ "code": "MAQAM2026" }` |
| `DELETE /store/cart/coupon` | — |

الكمية تُقصّ تلقائياً على المتاح في المخزون.

---

## 9. الطلبات والدفع (محمية)

### `POST /store/checkout`

```json
{
  "full_name": "أحمد محمود", "phone": "01012345678",
  "governorate": "القاهرة", "city": "مدينة نصر", "address": "شارع الطيران عمارة 12",
  "notes": "اتصل قبل التوصيل",
  "payment_method": "cod"
}
```

`payment_method`: `cod` | `wallet` | `kashier`.

رد `201`:

```json
{
  "message": "تم إنشاء طلبك بنجاح.",
  "data": {
    "order": { "order_number": "MQ-20260922-A812ZQ", "status": "new", "payment_method": "cod", "payment_status": "pending", "subtotal": "840.00", "discount": "0.00", "shipping_cost": "0.00", "total_amount": "840.00", "currency": "EGP", "coupon_code": null, "created_at": "…", "shipping_address": { }, "items": [ ] },
    "payment_url": null,
    "payment_return_url": null
  }
}
```

- **`cod`**: الطلب `new` والدفع `pending`.
- **`wallet`**: 10 نقاط = 1 جنيه. يُخصم المطلوب فوراً والطلب `processing` / `paid`. رصيد غير كافٍ ← `INSUFFICIENT_POINTS` ولا يُنشأ طلب.
- **`kashier`**: يرجع `payment_url`. افتحه في WebView؛ عندما ينتقل الـ WebView إلى رابط يبدأ بـ `payment_return_url` أغلقه ثم اطلب `GET /orders/{order_number}` لمعرفة `payment_status`. التأكيد النهائي يأتي من الخادم وليس من الـ WebView.
- الأسعار والإجمالي يحسبها الخادم دائماً؛ لا يُقبل أي سعر من التطبيق.
- عند أي خطأ لا يتغير المخزون ولا الرصيد ولا تُفرَّغ السلة.

### `GET /orders` — مقسّم لصفحات · `GET /orders/{order_number}`

`status`: `new` → `processing` → `shipped` → `delivered` (أو `cancelled` / `refunded`).
`payment_status`: `pending` | `paid` | `failed` | `refunded`.

### `GET /addresses`

عناوين الشحن السابقة (لتعبئة نموذج الطلب):

```json
{ "data": [ { "id": 3, "recipient_name": "…", "phone": "…", "governorate": "…", "city": "…", "address": "…", "notes": null, "is_default": true } ] }
```

---

## 10. الإشعارات (محمية)

| المسار | الوصف |
|---|---|
| `GET /notifications` | مقسّم لصفحات، و`meta.unread_count` عدد غير المقروء |
| `POST /notifications/{id}/read` | تعليم كمقروء |
| `POST /notifications/read-all` | تعليم الكل |

```json
{ "id": 7, "title": "…", "body": "…", "type": "order_update", "is_read": false, "read_at": null, "created_at": "…" }
```

`type`: `rank_upgrade` | `offer` | `reminder` | `order_update` | `promotion` | `merchant_update`.

### Push (Firebase Cloud Messaging)

كل إشعار يُحفظ في القائمة أعلاه **ويُرسل** للجهاز عبر FCM. المطلوب من التطبيق:

1. إضافة `firebase_messaging` مع ملفي `google-services.json` (أندرويد) و`GoogleService-Info.plist` (iOS) — المشروع `maqam-430eb` والـ package/bundle هو `maqam.egypt`.
2. بعد تسجيل الدخول، وعند كل `onTokenRefresh`: `POST /notifications/device-token`.
3. طلب إذن الإشعارات من المستخدم (iOS وأندرويد 13+).

شكل الرسالة الواصلة:

```json
{
  "notification": { "title": "تحديث الطلب MQ-20260922-A812ZQ", "body": "تم شحن طلبك وهو في الطريق إليك." },
  "data": { "type": "order_update", "order_number": "MQ-20260922-A812ZQ", "status": "shipped" }
}
```

- `data.type` موجود دائماً (نفس قيم `type` أعلاه) ويُستخدم لتحديد الشاشة التي تُفتح عند الضغط.
- `order_number` و`status` يأتيان مع `order_update` فقط. كل قيم `data` نصوص.
- `merchant_status` (`approved` / `rejected`) و`merchant_code` يأتيان مع النوع `merchant_update`.
- الإشعارات تُكتب بلغة المستخدم (`preferred_language`).
- كل أجهزة الحساب تستقبل: سجّل توكن كل جهاز، وأرسله مع `POST /auth/logout` (`device_token`) ليتوقف الجهاز عن الاستقبال.

---

## 11. التاجر (محمية)

### `POST /merchant/apply`

```json
{ "business_name": "معرض النور", "business_address": "…" }
```

ينشئ ملف تاجر بكود فريد و`is_approved = false` حتى موافقة الإدارة. الكود لا يعمل في المسح قبل الموافقة.

### `GET /merchant`

```json
{
  "data": {
    "id": 4, "merchant_code": "M-AB12CD34", "business_name": "معرض النور", "business_address": "…", "logo_url": null, "is_approved": true, "approved_at": "…",
    "stats": { "total_scans": 120, "total_points": 600, "scans_this_month": 18, "unique_customers": 37 }
  }
}
```

`404` إن لم يتقدم المستخدم بطلب تاجر.

---

## 12. ملاحظات أمان للتطبيق

- التوكن في Secure Storage فقط، ولا يُكتب في logs.
- HTTPS فقط، ويُفضّل certificate pinning.
- لا تثق في أي رصيد أو سعر محسوب محلياً؛ اعرض دائماً ما يرجعه الخادم.
- عند `401` امسح التوكن وارجع لتسجيل الدخول. عند `403 ACCOUNT_FROZEN_FRAUD` اعرض شاشة الحساب الموقوف.
- عند `429` احترم `Retry-After` ولا تعِد المحاولة تلقائياً بسرعة.

---

## 13. إضافات لاحقة (حذف الحساب، العناوين، الإلغاء، المحتوى، المساعد)

### بدء التشغيل: `GET /app/config?platform=android&version=1.4.2` — عام

```json
{ "data": { "update_required": false, "update_available": true, "minimum_version": "1.2.0", "latest_version": "1.5.0", "store_url": "https://play.google.com/…" } }
```

- `update_required = true`: اقفل التطبيق على شاشة "حدّث الآن" تفتح `store_url`.
- `update_available = true` فقط: تنبيه يمكن تجاهله.
- الأدمن يغيّر الأرقام من الإعدادات (مجموعة `app`).

### حذف الحساب: `DELETE /user/account` — محمي

```json
{ "confirm": "DELETE" }
```

شرط للقبول في App Store وGoogle Play: يجب أن يكون داخل التطبيق خلف تأكيد واضح. يمسح البيانات الشخصية ويلغي كل التوكنات ويحرّر رقم الجوال (يمكن التسجيل به من جديد كحساب جديد). لا يمكن التراجع.

### تغيير رقم الجوال — محمي

1. `POST /user/phone/start` بـ `{ "phone": "01212345678" }` ← نفس رد `auth/whatsapp/start`. الكود يُرسل من الرقم **الجديد**.
2. `POST /auth/whatsapp/check` بنفس `challenge_id` حتى `otp_sent = true`.
3. `POST /user/phone/verify` بـ `{ "challenge_id": "…", "otp": "842109" }`. التوكن الحالي يبقى صالحاً.

### دفتر العناوين — محمي

| المسار | الوصف |
|---|---|
| `GET /addresses` | العناوين المحفوظة، الافتراضي أولاً |
| `POST /addresses` | إضافة (أول عنوان يصبح افتراضياً) |
| `PUT /addresses/{id}` | تعديل (أرسل العنوان كاملاً) |
| `DELETE /addresses/{id}` | حذف (الطلبات السابقة تحتفظ بالعنوان) |

```json
{ "recipient_name": "أحمد محمود", "phone": "01012345678", "governorate": "القاهرة", "city": "مدينة نصر", "address": "شارع الطيران عمارة 12", "notes": null, "is_default": true }
```

في `POST /store/checkout` أرسل إما `address_id` أو حقول العنوان الخمسة (والعنوان المكتوب يُحفظ تلقائياً في الدفتر).

### استخدام جوائز العجلة

`POST /store/cart/coupon` يقبل كود كوبون عام **أو** `code` أي مكافأة متاحة من `GET /customer/rewards`:

- كوبون / خصم: يظهر في `discount`.
- منتج هدية: يُضاف للطلب كبند مجاني (`unit_price = "0.00"`).
- المكافأة تُستهلك عند إنشاء الطلب وترجع إذا أُلغي. كود مكافأة عميل آخر مرفوض.

### إلغاء الطلب: `POST /orders/{order_number}/cancel` — محمي

```json
{ "reason": "غيرت رأيي" }
```

مسموح طالما `can_cancel = true` في بيانات الطلب (الحالة `new` أو `processing` وغير مدفوع بالبطاقة). يرجع المخزون والكوبون والمكافأة، والطلب المدفوع بالنقاط تُرد نقاطه (`payment_status = refunded`). المدفوع بالبطاقة يُلغى عبر الدعم.

### المحتوى — عام

- `GET /content/faq` ← `[{ "id": 1, "question": "…", "answer": "…" }]`
- `GET /content/pages/{slug}` حيث `slug` = `privacy` | `terms` | `shipping` | `returns` ← `{ "slug", "title", "lead", "html", "url" }`. الحقل `url` هو رابط الصفحة على الموقع (لرابط سياسة الخصوصية في المتاجر).

### المساعد الذكي: `POST /chat` — محمي

```json
{ "message": "كم تكلفة الشحن؟", "history": [ { "role": "user", "text": "مرحبا" }, { "role": "assistant", "text": "أهلاً بك" } ] }
```

```json
{ "data": { "reply": "تكلفة الشحن 35 جنيه…", "messages_left_today": 59 } }
```

- يجيب عن أسئلة MAQAM فقط، ولا يرى الطلبات ولا ينفّذ أي إجراء.
- الخادم لا يحفظ المحادثة: أرسل آخر الرسائل في `history` (حتى 10).
- الحد: 10 رسائل/دقيقة و60/يوم لكل عميل. اعرض الرد كنص عادي.

### لوجو التاجر: `POST /merchant/logo` — محمي، multipart

حقل `logo` (JPG / PNG / WebP حتى 2MB). و`POST /merchant/apply` يقبل `logo` اختيارياً عند الإرسال كـ multipart. قرار الإدارة يصل كإشعار `merchant_update`.
