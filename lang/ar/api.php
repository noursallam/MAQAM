<?php

return [
    'validation_failed' => 'البيانات المرسلة غير صالحة.',
    'unauthenticated' => 'يجب تسجيل الدخول أولاً.',
    'forbidden' => 'غير مسموح بهذا الإجراء.',
    'not_found' => 'العنصر المطلوب غير موجود.',
    'too_many_requests' => 'محاولات كثيرة، حاول مرة أخرى بعد قليل.',
    'request_failed' => 'تعذر تنفيذ الطلب.',
    'server_error' => 'حدث خطأ غير متوقع، حاول مرة أخرى.',
    'account_frozen' => 'تم إيقاف الحساب مؤقتاً للمراجعة. تواصل مع الدعم.',
    'saved' => 'تم الحفظ.',

    'upload_failed' => 'تعذر رفع الملف. حاول مرة أخرى.',

    'account' => [
        'deleted' => 'تم حذف حسابك وبياناتك الشخصية.',
        'phone_taken' => 'هذا الرقم مستخدم بالفعل.',
        'phone_changed' => 'تم تغيير رقم الجوال.',
    ],

    'address' => [
        'not_found' => 'العنوان المحدد غير موجود.',
        'limit_reached' => 'وصلت للحد الأقصى من العناوين المحفوظة.',
    ],

    'chat' => [
        'unavailable' => 'المساعد غير متاح حالياً. حاول لاحقاً أو تواصل مع الدعم.',
        'limit_reached' => 'وصلت للحد اليومي من رسائل المساعد. حاول غداً.',
    ],

    'auth' => [
        'challenge_created' => 'أرسل الكود إلى رقمنا على واتساب لاستلام كود الدخول.',
        'signed_in' => 'تم تسجيل الدخول بنجاح.',
        'signed_out' => 'تم تسجيل الخروج.',
        'default_name' => 'عميل مقام',
        'invalid_credentials' => 'رقم الجوال أو كلمة المرور غير صحيحة.',
        'profile_completed' => 'تم حفظ بياناتك.',
        'password_updated' => 'تم تحديث كلمة المرور.',
        'wrong_current_password' => 'كلمة المرور الحالية غير صحيحة.',
        'verify_to_set_password' => 'ادخل بكود واتساب أولاً لتعيين كلمة مرور جديدة.',
    ],

    'qr' => [
        'locked' => 'تم إيقاف المسح مؤقتاً بسبب أكواد غير صحيحة متكررة. حاول بعد :minutes دقيقة.',
        'preview_ok' => 'كود صالح. ستحصل على :points نقطة.',
        'scan_ok' => 'تم مسح الكود وإضافة :points نقطة لمحفظتك.',
        'sync_ok' => 'تمت مزامنة :count كود.',
        'merchant_not_found' => 'كود التاجر غير موجود أو غير معتمد.',
        'self_scan' => 'لا يمكنك استخدام كود التاجر الخاص بك.',
    ],

    'wheel' => [
        'won' => 'مبروك! لقد فزت بجائزة.',
        'lost' => 'حظ أوفر المرة القادمة.',
        'no_prize' => 'حظ أوفر',
    ],

    'cart' => [
        'invalid_option' => 'الخيار المحدد لا يخص هذا المنتج.',
        'added' => 'تمت الإضافة إلى السلة.',
        'updated' => 'تم تحديث السلة.',
        'coupon_applied' => 'تم تطبيق الكوبون.',
    ],

    'checkout' => [
        'out_of_stock' => 'الكمية المطلوبة من «:product» غير متوفرة (المتاح: :available).',
        'gateway_error' => 'تعذر بدء الدفع الإلكتروني. لم يتم خصم أي مبلغ، حاول مرة أخرى.',
        'complete_payment' => 'أكمل الدفع عبر بوابة الدفع.',
        'placed' => 'تم إنشاء طلبك بنجاح.',
        'gift_item' => 'هدية من عجلة الحظ',
    ],

    'rank' => [
        'upgraded_title' => 'مبروك! رتبة جديدة',
        'upgraded_body' => 'تمت ترقيتك إلى رتبة :rank. استمتع بمزاياك الجديدة.',
    ],

    'order' => [
        'status_title' => 'تحديث الطلب :number',
        'placed' => 'تم استلام طلبك وسنبدأ تجهيزه.',
        'paid' => 'تم الدفع بنجاح، وطلبك جارٍ تجهيزه.',
        'payment_failed' => 'لم يكتمل الدفع، ولم يُخصم أي مبلغ.',
        'wa' => [
            'currency' => 'ج.م',
            'status' => 'الحالة الآن: :status',
            'status_new' => 'تم الاستلام',
            'status_processing' => 'جارٍ التجهيز',
            'status_shipped' => 'تم الشحن',
            'status_delivered' => 'تم التسليم',
            'status_cancelled' => 'ملغي',
            'status_refunded' => 'تم الاسترداد',
            'items' => 'المنتجات:',
            'item' => 'منتج',
            'free' => 'مجاناً',
            'subtotal' => 'المجموع: :amount',
            'discount' => 'الخصم: :amount',
            'shipping' => 'الشحن: :amount',
            'total' => 'الإجمالي: :amount',
            'payment' => 'الدفع: :method',
            'method_cod' => 'عند الاستلام',
            'method_kashier' => 'بطاقة أو محفظة إلكترونية',
            'method_wallet' => 'نقاط الولاء',
            'address' => 'العنوان: :address',
        ],
        'status_new' => 'تم استلام طلبك.',
        'status_processing' => 'طلبك جارٍ تجهيزه.',
        'status_shipped' => 'تم شحن طلبك وهو في الطريق إليك.',
        'status_delivered' => 'تم تسليم طلبك. شكراً لتسوقك معنا.',
        'status_cancelled' => 'تم إلغاء طلبك.',
        'status_refunded' => 'تم استرداد قيمة طلبك.',
        'cancelled' => 'تم إلغاء الطلب.',
        'not_cancellable' => 'لا يمكن إلغاء هذا الطلب من التطبيق. تواصل مع الدعم.',
    ],

    'merchant' => [
        'already_applied' => 'لديك طلب تاجر مسجل بالفعل.',
        'applied' => 'تم استلام طلبك وهو قيد مراجعة الإدارة.',
        'approved_title' => 'تم اعتمادك كتاجر',
        'approved_body' => 'مبروك! كود التاجر الخاص بك :code أصبح مفعّلاً.',
        'rejected_title' => 'طلب التاجر لم يُقبل',
        'rejected_body' => 'نأسف، لم نتمكن من قبول طلبك. السبب: :reason',
    ],
];
