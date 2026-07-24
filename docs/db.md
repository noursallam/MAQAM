**QR**:

### 🧑 المجموعة الأولى: المستخدمون والصلاحيات (Users & Roles)

#### 1. جدول `users`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|معرف فريد|
|`phone_number`|VARCHAR(20)|UNIQUE, NOT NULL|رقم الهاتف (وسيلة الدخول الأساسية)|
|`email`|VARCHAR(255)|UNIQUE|البريد الإلكتروني (اختياري)|
|`password_hash`|VARCHAR(255)|NOT NULL|كلمة المرور المشفرة (bcrypt/argon2)|
|`full_name`|VARCHAR(255)|NOT NULL|الاسم الكامل للمستخدم|
|`role`|ENUM|NOT NULL, DEFAULT 'customer' (`customer`, `merchant`, `admin`)|دور المستخدم|
|`is_active`|BOOLEAN|DEFAULT TRUE|حالة الحساب (معطل/نشط)|
|`phone_verified_at`|TIMESTAMP|NULL|تاريخ تحقق رقم الهاتف|
|`last_login_at`|TIMESTAMP|NULL|آخر تسجيل دخول|
|`device_token`|TEXT|NULL|رمز جهاز الإشعارات (FCM/APNS)|
|`preferred_language`|ENUM|DEFAULT 'ar' (`ar`, `en`)|اللغة المفضلة للمستخدم|
|`face_id_enabled`|BOOLEAN|DEFAULT FALSE|هل تم تفعيل Face ID/Fingerprint؟|
|`face_id_token`|VARCHAR(255)|NULL|رمز المصادقة الحيوية المشفر|
|`otp_code`|VARCHAR(6)|NULL|كود التحقق المؤقت (OTP)|
|`otp_expires_at`|TIMESTAMP|NULL|انتهاء صلاحية الكود|
|`created_at`|TIMESTAMP|-|تاريخ الإنشاء|
|`updated_at`|TIMESTAMP|-|تاريخ آخر تحديث|

#### 2. جدول `customers`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|معرف العميل|
|`user_id`|BIGINT|FK → users.id, UNIQUE|الربط بالمستخدم (علاقة 1:1)|
|`rank_id`|BIGINT|FK → ranks.id|الرتبة الحالية للعميل|
|`points_balance`|INTEGER|DEFAULT 0|رصيد النقاط الحالي (يُحدث تلقائياً)|
|`total_points_earned`|INTEGER|DEFAULT 0|إجمالي النقاط المكتسبة طوال التاريخ|
|`total_points_spent`|INTEGER|DEFAULT 0|إجمالي النقاط المنفقة (عجلة الحظ، خصومات)|
|`date_of_birth`|DATE|NULL|تاريخ الميلاد (للعروض المخصصة)|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 3. جدول `merchants`

| **الحقل (Column)**        | **النوع (Data Type)** | **القيد (Constraints)** | **الوصف (Description)**                |
| ------------------------- | --------------------- | ----------------------- | -------------------------------------- |
| `id`                      | BIGINT                | PK                      | معرف التاجر                            |
| `user_id`                 | BIGINT                | FK → users.id, UNIQUE   | الربط بالمستخدم                        |
| `business_name`           | VARCHAR(255)          | NOT NULL                | اسم النشاط التجاري                     |
| `business_address`        | TEXT                  | -                       | العنوان الكامل للنشاط                  |
| `merchant_code`           | VARCHAR(50)           | UNIQUE, NOT NULL        | كود التاجر الفريد (يُستخدم في التطبيق) |
| `is_approved`             | BOOLEAN               | DEFAULT FALSE           | هل تمت الموافقة على التاجر؟            |
| `approved_at`             | TIMESTAMP             | NULL                    | تاريخ الموافقة                         |
| `approved_by`             | BIGINT                | FK → admins.id          | المدير الذي وافق                       |
| `logo_url`                | VARCHAR(255)          | NULL                    | رابط شعار التاجر                       |
| `created_at`              | TIMESTAMP             | -                       | -                                      |
| `updated_at`              | TIMESTAMP             | -                       | -                                      |

#### 4. جدول `admins`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|معرف المدير|
|`user_id`|BIGINT|FK → users.id, UNIQUE|الربط بالمستخدم|
|`role`|ENUM|NOT NULL (`super_admin`, `content_manager`, `support`, `finance`)|صلاحيات المدير|
|`permissions`|JSON|NULL|صلاحيات إضافية (RBAC) بصيغة JSON|
|`last_activity_at`|TIMESTAMP|NULL|آخر نشاط للمدير في لوحة التحكم|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

### 🏅 المجموعة الثانية: الولاء والنقاط والرتب (Loyalty & Ranks)

#### 5. جدول `ranks`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`name_en`|VARCHAR(50)|NOT NULL|الاسم بالإنجليزية (Silver/Gold/Platinum)|
|`name_ar`|VARCHAR(50)|NOT NULL|الاسم بالعربية (فضي/ذهبي/بلاتيني)|
|`min_points`|INTEGER|NOT NULL|الحد الأدنى من النقاط لهذه الرتبة|
|`max_points`|INTEGER|NULL|الحد الأقصى (NULL للرتبة الأعلى)|
|`customer_points_per_scan`|INTEGER|NOT NULL|عدد النقاط التي يحصل عليها العميل عند مسح كود من هذه الرتبة|
|`merchant_points_per_scan`|INTEGER|NOT NULL|عدد النقاط التي يحصل عليها التاجر من نفس المسح|
|`wheel_win_probability`|FLOAT|NOT NULL|احتمالية الفوز في عجلة الحظ (0.0 - 1.0)|
|`wheel_cost_points`|INTEGER|DEFAULT 50|تكلفة المحاولة في عجلة الحظ (نقاط)|
|`icon_url`|VARCHAR(255)|NULL|أيقونة الرتبة (صورة)|
|`is_active`|BOOLEAN|DEFAULT TRUE|هل الرتبة مفعلة حالياً؟|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 6. جدول `points_transactions`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`customer_id`|BIGINT|FK → customers.id, NOT NULL|العميل المعني بالمعاملة|
|`merchant_id`|BIGINT|FK → merchants.id|التاجر (إن وجد)|
|`qr_scan_id`|BIGINT|FK → qr_scans.id|عملية المسح المرتبطة (إن وجدت)|
|`type`|ENUM|NOT NULL (`earn`, `spend`, `refund`, `expire`, `adjust`)|نوع المعاملة|
|`amount`|INTEGER|NOT NULL|عدد النقاط (قد يكون سالباً للصرف)|
|`description`|TEXT|-|وصف مختصر للمعاملة|
|`balance_after`|INTEGER|-|رصيد العميل بعد هذه المعاملة|
|`admin_id`|BIGINT|FK → admins.id|المدير الذي أجرى تعديلاً (إن وجد)|
|`transaction_date`|TIMESTAMP|DEFAULT CURRENT_TIMESTAMP|تاريخ المعاملة|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

### 📷 المجموعة الثالثة: الأكواد والمسح (QR Codes & Scans)

#### 7. جدول `qr_codes` (**محدث**)

| **الحقل (Column)**    | **النوع (Data Type)** | **القيد (Constraints)**                        | **الوصف (Description)**                                     |
| --------------------- | --------------------- | ---------------------------------------------- | ----------------------------------------------------------- |
| `id`                  | BIGINT                | PK                                             | -                                                           |
| `serial_code`         | VARCHAR(16)           | UNIQUE, NOT NULL                               | الكود التسلسلي المكون من 16 رقمًا (مشفر)                    |
| `category_id`         | BIGINT                | FK → categories_prize.id, NULL                 | فئة الهدايا المرتبطة بالكود                                 |
| `points_awarded`      | INTEGER               | DEFAULT 0                                      | النقاط الثابتة المخصصة لهذا الكود بناءً على الفئة           |
| `status`              | ENUM                  | DEFAULT 'active' (`active`, `used`, `expired`) | حالة الكود                                                  |
| `generated_at`        | TIMESTAMP             | DEFAULT CURRENT_TIMESTAMP                      | تاريخ الإنشاء                                               |
| `used_at`             | TIMESTAMP             | NULL                                           | تاريخ الاستخدام (عند المسح)                                 |
| `used_by_customer_id` | BIGINT                | FK → customers.id                              | العميل الذي استخدم الكود                                    |
| `batch_id`            | VARCHAR(50)           | -                                              | معرف الدفعة التي صدر فيها الكود (للتتبع)                    |
| `created_at`          | TIMESTAMP             | -                                              | -                                                           |
| `updated_at`          | TIMESTAMP             | -                                              | -                                                           |

> **ملاحظة:** الكود **لا يُربط بتاجر عند التوليد**. ربط التاجر يتم وقت المسح فقط عبر إدخال/اختيار `merchant_code` ويُسجَّل في `qr_scans.merchant_id`.

#### 8. جدول `qr_scans`

| **الحقل (Column)**        | **النوع (Data Type)** | **القيد (Constraints)**                           | **الوصف (Description)**                                                      |
| ------------------------- | --------------------- | ------------------------------------------------- | ---------------------------------------------------------------------------- |
| `id`                      | BIGINT                | PK                                                | -                                                                            |
| `qr_code_id`              | BIGINT                | FK → qr_codes.id, NOT NULL                        | الكود الممسوح                                                                |
| `customer_id`             | BIGINT                | FK → customers.id, NOT NULL                       | العميل الذي قام بالمسح                                                       |
| `merchant_id`             | BIGINT                | FK → merchants.id, NULL                           | التاجر المختار وقت المسح (بإدخال الكود أو من قائمة التجار السابقين للعميل) |
| `points_awarded_customer` | INTEGER               | NOT NULL                                          | النقاط التي حصل عليها العميل (ثابتة للهدية أو حسب الرتبة)                    |
| `points_awarded_merchant` | INTEGER               | DEFAULT 0                                         | النقاط التي حصل عليها التاجر (0 إذا لم يُحدد تاجر)                           |
| `scan_location_lat`       | VARCHAR(50)           | -                                                 | خط العرض (لمكافحة الاحتيال)                                                  |
| `scan_location_lng`       | VARCHAR(50)           | -                                                 | خط الطول                                                                     |
| `scanned_at`              | TIMESTAMP             | DEFAULT CURRENT_TIMESTAMP                         | وقت المسح                                                                    |
| `is_offline`              | BOOLEAN               | DEFAULT FALSE                                     | هل تم المسح في وضع عدم الاتصال؟                                              |
| `sync_status`             | ENUM                  | DEFAULT 'pending' (`pending`, `synced`, `failed`) | حالة مزامنة البيانات (للمسح غير المتصل)                                      |
| `device_id`               | VARCHAR(255)          | -                                                 | معرف الجهاز الذي قام بالمسح                                                  |
| `created_at`              | TIMESTAMP             | -                                                 | -                                                                            |
| `updated_at`              | TIMESTAMP             | -                                                 | -                                                                            |

### 🛍️ المجموعة الرابعة: المتجر الإلكتروني (E‑Commerce)

#### 9. جدول `categories` (**محدث**)

| **الحقل (Column)** | **النوع (Data Type)** | **القيد (Constraints)**  | **الوصف (Description)**                                            |
| ------------------ | --------------------- | ------------------------ | ------------------------------------------------------------------ |
| `id`               | BIGINT                | PK                       | معرف الفئة                                                         |
| `name_en`          | VARCHAR(100)          | NOT NULL                 | اسم الفئة بالإنجليزية                                              |
| `name_ar`          | VARCHAR(100)          | NOT NULL                 | اسم الفئة بالعربية                                                 |
| `slug`             | VARCHAR(100)          | UNIQUE, NOT NULL         | رابط مختصر للفئة                                                   |
| `parent_id`        | BIGINT                | FK → categories.id, NULL | الفئة الأب (للتسلسل الهرمي)                                        |
| `icon`             | VARCHAR(255)          | NULL                     | أيقونة الفئة                                                       |
| `is_active`        | BOOLEAN               | DEFAULT TRUE             | هل الفئة مفعلة؟                                                    |
| `created_at`       | TIMESTAMP             | -                        | تاريخ الإنشاء                                                      |
| `updated_at`       | TIMESTAMP             | -                        | تاريخ التحديث                                                      |
#### 9. جدول `categories_prize` 

| **الحقل (Column)** | **النوع (Data Type)** | **القيد (Constraints)**                           | **الوصف (Description)**                                            |
| ------------------ | --------------------- | ------------------------------------------------- | ------------------------------------------------------------------ |
| `id`               | BIGINT                | PK                                                | معرف الفئة                                                         |
| `name_en`          | VARCHAR(100)          | NOT NULL                                          | اسم الفئة بالإنجليزية                                              |
| `name_ar`          | VARCHAR(100)          | NOT NULL                                          | اسم الفئة بالعربية                                                 |
| `category_type`    | ENUM                  | NOT NULL, DEFAULT 'standard' (`standard`, `gift`) | **جديد:** نوع الفئة (عادية للمنتجات أم فئة هدايا خاصة بالنقاط)     |
| `points_value`     | INTEGER               | DEFAULT 0                                         | **جديد:** عدد النقاط الثابتة للهدية (إذا كانت الفئة مخصصة للهدايا) |
| `icon`             | VARCHAR(255)          | NULL                                              | أيقونة الفئة                                                       |
| `is_active`        | BOOLEAN               | DEFAULT TRUE                                      | هل الفئة مفعلة؟                                                    |
| `created_at`       | TIMESTAMP             | -                                                 | تاريخ الإنشاء                                                      |
| `updated_at`       | TIMESTAMP             | -                                                 | تاريخ التحديث                                                      |

#### 10. جدول `products`

| **الحقل (Column)** | **النوع (Data Type)** | **القيد (Constraints)**                 | **الوصف (Description)**           |
| ------------------ | --------------------- | --------------------------------------- | --------------------------------- |
| `id`               | BIGINT                | PK                                      | -                                 |
| `category_id`      | BIGINT                | FK → categories.id                      | الفئة التي ينتمي إليها المنتج     |
| `name_en`          | VARCHAR(255)          | NOT NULL                                | اسم المنتج بالإنجليزية            |
| `name_ar`          | VARCHAR(255)          | NOT NULL                                | اسم المنتج بالعربية               |
| `description_en`   | TEXT                  | NULL                                    | وصف المنتج بالإنجليزية            |
| `description_ar`   | TEXT                  | NULL                                    | وصف المنتج بالعربية               |
| `price`            | DECIMAL(10,2)         | NOT NULL                                | سعر المنتج                        |
| `stock_quantity`   | INTEGER               | DEFAULT 0                               | الكمية المتوفرة في المخزون        |
| `sku`              | VARCHAR(100)          | UNIQUE                                  | رمز التخزين الفريد (SKU)          |
| `image_url`        | VARCHAR(255)          | NULL                                    | رابط الصورة الرئيسية              |
| `is_active`        | BOOLEAN               | DEFAULT TRUE                            | هل المنتج متاح للبيع؟             |
| `weight`           | DECIMAL(8,2)          | NULL                                    | وزن المنتج (للشحن)                |
| `dimensions`       | VARCHAR(100)          | NULL                                    | الأبعاد (طول×عرض×ارتفاع)          |
| `created_at`       | TIMESTAMP             | -                                       | -                                 |
| `updated_at`       | TIMESTAMP             | -                                       | -                                 |

#### 11. جدول `cart`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`user_id`|BIGINT|FK → users.id, NOT NULL|المستخدم صاحب السلة|
|`session_id`|VARCHAR(255)|NULL|معرف الجلسة (للمستخدم غير المسجل)|
|`expires_at`|TIMESTAMP|NOT NULL|وقت انتهاء صلاحية السلة|
|`coupon_code`|VARCHAR(50)|NULL|كود الخصم المطبق (إن وجد)|
|`total`|DECIMAL(10,2)|DEFAULT 0.00|إجمالي قيمة السلة (محسوب)|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 12. جدول `cart_items`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`cart_id`|BIGINT|FK → cart.id, NOT NULL|السلة التي ينتمي إليها العنصر|
|`product_id`|BIGINT|FK → products.id, NOT NULL|المنتج المضاف|
|`quantity`|INTEGER|NOT NULL, DEFAULT 1|الكمية المطلوبة|
|`unit_price`|DECIMAL(10,2)|NOT NULL|سعر الوحدة عند الإضافة|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 13. جدول `shipping_addresses`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`user_id`|BIGINT|FK → users.id, NOT NULL|المستخدم صاحب العنوان|
|`address_line1`|VARCHAR(255)|NOT NULL|العنوان الأول|
|`address_line2`|VARCHAR(255)|NULL|العنوان الثاني (اختياري)|
|`city`|VARCHAR(100)|NOT NULL|المدينة|
|`governorate`|VARCHAR(100)|NOT NULL|المحافظة|
|`country`|VARCHAR(100)|DEFAULT 'Egypt'|الدولة|
|`postal_code`|VARCHAR(20)|NULL|الرمز البريدي|
|`phone`|VARCHAR(20)|NOT NULL|رقم هاتف الاستلام|
|`recipient_name`|VARCHAR(255)|NOT NULL|اسم المستلم|
|`is_default`|BOOLEAN|DEFAULT FALSE|هل هذا العنوان الافتراضي؟|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 14. جدول `orders`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`user_id`|BIGINT|FK → users.id, NOT NULL|المستخدم الذي أنشأ الطلب|
|`order_number`|VARCHAR(50)|UNIQUE, NOT NULL|رقم الطلب الفريد (قابل للقراءة)|
|`status`|ENUM|DEFAULT 'new' (`new`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`)|حالة الطلب|
|`subtotal`|DECIMAL(10,2)|NOT NULL|إجمالي قيمة المنتجات قبل الخصم|
|`tax`|DECIMAL(10,2)|DEFAULT 0.00|الضريبة المضافة|
|`discount`|DECIMAL(10,2)|DEFAULT 0.00|قيمة الخصم المطبق|
|`shipping_cost`|DECIMAL(10,2)|DEFAULT 0.00|تكلفة الشحن|
|`total_amount`|DECIMAL(10,2)|NOT NULL|المبلغ النهائي بعد الخصم والضرائب|
|`payment_method`|ENUM|NOT NULL (`cod`, `paymob`, `wallet`)|طريقة الدفع|
|`payment_status`|ENUM|DEFAULT 'pending' (`pending`, `paid`, `failed`, `refunded`)|حالة الدفع|
|`shipping_address_id`|BIGINT|FK → shipping_addresses.id|عنوان الشحن المستخدم|
|`shipped_at`|TIMESTAMP|NULL|تاريخ الشحن|
|`delivered_at`|TIMESTAMP|NULL|تاريخ التسليم|
|`cancelled_at`|TIMESTAMP|NULL|تاريخ الإلغاء|
|`cancellation_reason`|TEXT|NULL|سبب الإلغاء (إن وجد)|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 15. جدول `order_items`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`order_id`|BIGINT|FK → orders.id, NOT NULL|الطلب الذي ينتمي إليه العنصر|
|`product_id`|BIGINT|FK → products.id, NOT NULL|المنتج المطلوب|
|`quantity`|INTEGER|NOT NULL|الكمية|
|`unit_price`|DECIMAL(10,2)|NOT NULL|سعر الوحدة في وقت الطلب|
|`subtotal`|DECIMAL(10,2)|NOT NULL|إجمالي سعر العنصر (الكمية × السعر)|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 16. جدول `payments`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`order_id`|BIGINT|FK → orders.id, NOT NULL|الطلب المرتبط بالدفع|
|`transaction_id`|VARCHAR(100)|UNIQUE, NOT NULL|معرف المعاملة من بوابة الدفع|
|`gateway`|ENUM|NOT NULL (`paymob`, `cod`, `wallet`)|بوابة الدفع المستخدمة|
|`amount`|DECIMAL(10,2)|NOT NULL|المبلغ المدفوع|
|`status`|ENUM|DEFAULT 'pending' (`pending`, `success`, `failed`, `refunded`)|حالة الدفع|
|`gateway_response`|JSON|NULL|استجابة البوابة (كاملة)|
|`paid_at`|TIMESTAMP|NULL|تاريخ الدفع الناجح|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

### 🎰 المجموعة الخامسة: التلعيب والعجلة (Gamification)

#### 17. جدول `wheel_spins`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`customer_id`|BIGINT|FK → customers.id, NOT NULL|العميل الذي قام بالدوران|
|`rank_id`|BIGINT|FK → ranks.id, NOT NULL|الرتبة التي كان عليها العميل وقت الدوران|
|`points_cost`|INTEGER|NOT NULL|عدد النقاط التي دفعها (حسب الرتبة)|
|`points_won`|INTEGER|DEFAULT 0|عدد النقاط التي ربحها (0 إذا خسر)|
|`prize_type`|ENUM|NOT NULL (`points`, `discount`, `coupon`, `none`)|نوع الجائزة|
|`prize_value`|VARCHAR(255)|NULL|قيمة الجائزة|
|`is_win`|BOOLEAN|DEFAULT FALSE|هل فاز؟|
|`probability_used`|FLOAT|NOT NULL|الاحتمالية الفعلية المستخدمة (حسب الرتبة)|
|`spun_at`|TIMESTAMP|DEFAULT CURRENT_TIMESTAMP|وقت الدوران|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

#### 18. جدول `coupons`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`code`|VARCHAR(50)|UNIQUE, NOT NULL|كود الكوبون الفريد|
|`type`|ENUM|NOT NULL (`percentage`, `fixed`)|نوع الخصم (نسبة مئوية أو قيمة ثابتة)|
|`value`|DECIMAL(10,2)|NOT NULL|قيمة الخصم|
|`scope`|ENUM|NOT NULL (`all`, `category`, `product`, `merchant`)|نطاق التطبيق|
|`merchant_id`|BIGINT|FK → merchants.id|التاجر المعني (إذا كان النطاق merchant)|
|`category_id`|BIGINT|FK → categories.id|الفئة المعنية (إذا كان النطاق category)|
|`product_id`|BIGINT|FK → products.id|المنتج المعني (إذا كان النطاق product)|
|`valid_from`|DATETIME|NOT NULL|تاريخ بدء الصلاحية|
|`valid_to`|DATETIME|NOT NULL|تاريخ انتهاء الصلاحية|
|`usage_limit`|INTEGER|NULL|الحد الأقصى لعدد مرات الاستخدام|
|`used_count`|INTEGER|DEFAULT 0|عدد مرات الاستخدام حتى الآن|
|`min_order_amount`|DECIMAL(10,2)|DEFAULT 0.00|الحد الأدنى لقيمة الطلب لتفعيل الكوبون|
|`is_active`|BOOLEAN|DEFAULT TRUE|هل الكوبون مفعل؟|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|

### 🛠️ المجموعة السادسة: الإدارة والإشعارات (Admin & Notifications)

#### 19. جدول `notifications`

| **الحقل (Column)** | **النوع (Data Type)** | **القيد (Constraints)**                                                     | **الوصف (Description)**              |
| ------------------ | --------------------- | --------------------------------------------------------------------------- | ------------------------------------ |
| `id`               | BIGINT                | PK                                                                          | -                                    |
| `user_id`          | BIGINT                | FK → users.id, NOT NULL                                                     | المستخدم المستهدف                    |
| `title`            | VARCHAR(255)          | NOT NULL                                                                    | عنوان الإشعار                        |
| `body`             | TEXT                  | NOT NULL                                                                    | نص الإشعار                           |
| `type`             | ENUM                  | NOT NULL (`rank_upgrade`, `offer`, `reminder`, `order_update`, `promotion`) | نوع الإشعار                          |
| `is_read`          | BOOLEAN               | DEFAULT FALSE                                                               | هل تمت قراءته؟                       |
| `read_at`          | TIMESTAMP             | NULL                                                                        | تاريخ القراءة                        |
| `created_at`       | TIMESTAMP             | -                                                                           | -                                    |
| `updated_at`       | TIMESTAMP             | -                                                                           | -                                    |

### ⚙️ المجموعة السابعة: الإعدادات (Settings)

#### 20. جدول `system_settings`

|**الحقل (Column)**|**النوع (Data Type)**|**القيد (Constraints)**|**الوصف (Description)**|
|---|---|---|---|
|`id`|BIGINT|PK|-|
|`key`|VARCHAR(100)|UNIQUE, NOT NULL|مفتاح الإعداد (مثل 'wheel_cost', 'low_stock_threshold')|
|`value`|TEXT|NOT NULL|القيمة (قد تكون نصاً أو JSON)|
|`group`|VARCHAR(50)|NOT NULL|المجموعة (general, loyalty, wheel, points, security)|
|`description`|TEXT|NULL|شرح الإعداد|
|`is_public`|BOOLEAN|DEFAULT FALSE|هل هذا الإعداد متاح للواجهات الأمامية (API)؟|
|`created_at`|TIMESTAMP|-|-|
|`updated_at`|TIMESTAMP|-|-|
