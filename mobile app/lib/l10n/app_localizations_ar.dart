// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'مقام';

  @override
  String get onboardingStart => 'ابدأ الآن';

  @override
  String get onboardingGuest => 'تصفّح المتجر كزائر';

  @override
  String get loginTitle => 'تسجيل الدخول';

  @override
  String get loginSubtitle =>
      'أدخل رقم جوالك. بدون كلمة مرور: ستؤكد الرقم برسالة قصيرة من واتساب.';

  @override
  String get loginPhoneLabel => 'رقم الجوال';

  @override
  String get loginContinue => 'متابعة';

  @override
  String get loginNoSpam => 'لن نرسل لك أي رسالة قبل أن تراسلنا أنت أولاً.';

  @override
  String get loginPhoneRequired => 'أدخل رقم الجوال.';

  @override
  String get loginPhoneInvalid => 'رقم الجوال غير صحيح. مثال: 10 1234 5678';

  @override
  String get loginLegalPrefix => 'بالمتابعة توافق على ';

  @override
  String get loginLegalTerms => 'الشروط والأحكام';

  @override
  String get loginLegalAnd => ' و';

  @override
  String get loginLegalPrivacy => 'سياسة الخصوصية';

  @override
  String get switchLanguage => 'English';

  @override
  String get back => 'رجوع';

  @override
  String get catalogAll => 'الكل';

  @override
  String get catalogLoadFailed => 'تعذر تحميل المنتجات';

  @override
  String get catalogEmpty => 'لا توجد منتجات';

  @override
  String get retry => 'إعادة المحاولة';

  @override
  String get priceOnRequest => 'السعر عند الطلب';

  @override
  String priceEgp(String amount) {
    return '$amount ج.م';
  }

  @override
  String stepOf(int step, int total) {
    return 'الخطوة $step من $total';
  }

  @override
  String get sendCodeTitle => 'أرسل الكود من واتساب';

  @override
  String get sendCodeSubtitlePrefix => 'أرسله من الرقم ';

  @override
  String get sendCodeSubtitleSuffix => ' وسنرد عليك بكود الدخول.';

  @override
  String get sendCodeLabel => 'كود التأكيد';

  @override
  String get sendCodeCopy => 'نسخ الكود';

  @override
  String get sendCodeCopied => 'تم نسخ الكود';

  @override
  String get sendCodeOpenWhatsapp => 'فتح واتساب وإرسال الكود';

  @override
  String get sendCodeWhatsappMissing =>
      'تعذر فتح واتساب. انسخ الكود وأرسله يدوياً.';

  @override
  String get sendCodeWaiting => 'في انتظار رسالتك...';

  @override
  String get sendCodeStep1 => 'اضغط الزر: الرسالة مكتوبة جاهزة';

  @override
  String get sendCodeStep2 => 'اضغط «إرسال» داخل واتساب';

  @override
  String get sendCodeStep3 => 'ارجع للتطبيق واكتب كود الدخول الذي يصلك';

  @override
  String sendCodeValidFor(int minutes) {
    return 'الكود صالح لمدة $minutes دقائق · ';
  }

  @override
  String get sendCodeChangeNumber => 'تغيير الرقم';

  @override
  String get sendCodeExpired => 'انتهت صلاحية الكود.';

  @override
  String get startOver => 'ابدأ من جديد';

  @override
  String get otpTitle => 'أدخل كود الدخول';

  @override
  String get otpSubtitle => 'وصلتنا رسالتك، وأرسلنا لك الكود على واتساب.';

  @override
  String get otpFieldLabel => 'كود الدخول المكوّن من 6 أرقام';

  @override
  String get otpExpiresIn => 'ينتهي الكود خلال ';

  @override
  String get otpExpired => 'انتهت صلاحية كود الدخول.';

  @override
  String get otpSubmit => 'دخول';

  @override
  String get otpNotReceivedPrefix =>
      'لم يصلك الكود؟ تأكد أنك أرسلت من نفس الرقم، ثم ';

  @override
  String get otpDoNotShare =>
      'لا تشارك هذا الكود مع أي شخص. فريق MAQAM لن يطلبه منك أبداً.';

  @override
  String get errorNetwork => 'تعذر الاتصال. تحقق من الإنترنت وحاول مرة أخرى.';

  @override
  String get errorGeneric => 'حدث خطأ غير متوقع. حاول مرة أخرى.';

  @override
  String get signIn => 'تسجيل الدخول';

  @override
  String get signOut => 'تسجيل الخروج';

  @override
  String get skip => 'تخطي';

  @override
  String get biometricSetupTitle => 'ادخل أسرع المرة القادمة';

  @override
  String get biometricSetupPoint1 =>
      'بصمتك تبقى على جهازك فقط، ولا تُرسل إلينا';

  @override
  String get biometricSetupPoint2 => 'تقدر توقفه في أي وقت من «حسابي»';

  @override
  String get biometricSetupPoint3 => 'الدخول بكود واتساب يبقى متاحاً دائماً';

  @override
  String get biometricSetupNotNow => 'ليس الآن';

  @override
  String get biometricSetupFailed =>
      'لم نتمكن من التحقق. حاول مرة أخرى أو اختر «ليس الآن».';

  @override
  String get biometricEnableReason =>
      'أكّد هويتك لتفعيل الدخول بالبصمة أو الوجه';

  @override
  String get unlockWelcomeBack => 'أهلاً بعودتك';

  @override
  String unlockWelcomeBackName(String name) {
    return 'أهلاً بعودتك، $name';
  }

  @override
  String get unlockFailed => 'لم نتمكن من التحقق. حاول مرة أخرى.';

  @override
  String get unlockReason => 'افتح تطبيق مقام';

  @override
  String get unlockUseWhatsapp => 'الدخول بكود واتساب';

  @override
  String get unlockDifferentAccount => 'ليس حسابك؟ دخول برقم آخر';

  @override
  String get navHome => 'الرئيسية';

  @override
  String get navStore => 'المتجر';

  @override
  String get navScan => 'مسح كود';

  @override
  String get navWallet => 'المحفظة';

  @override
  String get navAccount => 'حسابي';

  @override
  String homeGreeting(String name) {
    return 'أهلاً $name · رصيد نقاطك';
  }

  @override
  String get homeGreetingNoName => 'أهلاً بك · رصيد نقاطك';

  @override
  String get pointsUnit => 'نقطة';

  @override
  String homeToNextRank(int points, String rank) {
    return 'باقي $points نقطة للوصول إلى رتبة $rank';
  }

  @override
  String get homeTopRank => 'أنت في أعلى رتبة';

  @override
  String get rankUpCongrats => 'مبروك!';

  @override
  String rankUpMessage(String rank) {
    return 'لقد تمت ترقيتك إلى رتبة $rank';
  }

  @override
  String get rankUpDismiss => 'تمام';

  @override
  String get homeGuestTitle => 'اكسب نقاطاً مع كل عبوة';

  @override
  String get homeGuestBody =>
      'سجّل الدخول لتمسح الأكواد وتجمع النقاط والجوائز.';

  @override
  String get actionScan => 'مسح كود';

  @override
  String get actionWheel => 'عجلة الحظ';

  @override
  String get actionRewards => 'مكافآتي';

  @override
  String get actionOrders => 'طلباتي';

  @override
  String get actionLeaderboard => 'المتصدرين';

  @override
  String get leaderboardTitle => 'لوحة المتصدرين';

  @override
  String get leaderboardEmpty => 'لا يوجد متصدرين بعد';

  @override
  String leaderboardPoints(String points) {
    return '$points نقطة';
  }

  @override
  String get homeNewArrivals => 'وصل حديثاً';

  @override
  String get viewAll => 'عرض الكل';

  @override
  String get chat => 'المساعد';

  @override
  String get notifications => 'الإشعارات';

  @override
  String get storeSearchHint => 'ابحث باسم المنتج أو الكود';

  @override
  String storeCount(int count) {
    return '$count منتج';
  }

  @override
  String get cart => 'السلة';

  @override
  String get sortNewest => 'الأحدث';

  @override
  String get sortPriceLow => 'السعر: من الأقل';

  @override
  String get sortPriceHigh => 'السعر: من الأعلى';

  @override
  String get sortBy => 'الترتيب';

  @override
  String get outOfStock => 'غير متوفر';

  @override
  String get walletAvailable => 'متاح للصرف';

  @override
  String get walletTotalEarned => 'إجمالي ما كسبته';

  @override
  String walletNextRankAt(String rank, int points) {
    return '$rank عند $points';
  }

  @override
  String get walletRankNote =>
      'الرتبة تُحسب على إجمالي ما كسبته: الصرف لا ينزّل رتبتك.';

  @override
  String get walletEarn => 'اكسب نقاطاً';

  @override
  String get walletRecent => 'آخر الحركات';

  @override
  String get filterEarned => 'كسب';

  @override
  String get filterSpent => 'صرف';

  @override
  String get walletNoTransactions => 'لا توجد حركات بعد';

  @override
  String get walletLoadFailed => 'تعذر تحميل المحفظة';

  @override
  String get walletGuest => 'سجّل الدخول لعرض نقاطك وحركاتك.';

  @override
  String get today => 'اليوم';

  @override
  String get yesterday => 'أمس';

  @override
  String get accountEdit => 'تعديل';

  @override
  String get accountMerchantTitle => 'الانضمام كتاجر معتمد';

  @override
  String get accountMerchantBody => 'اكسب نقاطاً مع كل مسح بكودك';

  @override
  String get accountAddresses => 'عناويني';

  @override
  String get accountPendingScans => 'مسحات في انتظار الرفع';

  @override
  String get accountBiometricsOff => 'غير مفعّل';

  @override
  String get accountChangePhone => 'تغيير رقم الجوال';

  @override
  String get accountLanguage => 'اللغة';

  @override
  String get languageName => 'العربية';

  @override
  String get accountHelp => 'المساعد والأسئلة الشائعة';

  @override
  String get accountLegal => 'الشروط وسياسة الخصوصية';

  @override
  String get accountDelete => 'حذف الحساب';

  @override
  String accountVersion(String version) {
    return 'الإصدار $version';
  }

  @override
  String get accountGuest => 'سجّل الدخول لإدارة حسابك وطلباتك.';

  @override
  String get accountNoName => 'عميل مقام';

  @override
  String get signOutConfirmTitle => 'تسجيل الخروج؟';

  @override
  String get signOutConfirmBody => 'ستحتاج إلى كود واتساب للدخول مرة أخرى.';

  @override
  String get cancel => 'إلغاء';

  @override
  String get onboardingNext => 'التالي';

  @override
  String onboardingPage(int current, int total) {
    return 'الشاشة $current من $total';
  }

  @override
  String get onboarding1Title => 'الفخامة في كل لمسة';

  @override
  String get onboarding1Body =>
      'تسوّق مفاتيح وبرايز وإضاءة MAQAM، واطلبها إلى باب بيتك.';

  @override
  String get onboarding1ChipA => 'المتجر';

  @override
  String get onboarding1ChipB => 'توصيل للمنزل';

  @override
  String get onboarding2Title => 'امسح واكسب';

  @override
  String get onboarding2Body =>
      'امسح الكود الموجود على كل عبوة لتضيف نقاطاً إلى رصيدك فوراً.';

  @override
  String get onboarding2ChipA => 'كود العبوة';

  @override
  String get onboarding2ChipB => 'نقاط فورية';

  @override
  String get onboarding3Title => 'رتبتك ترتفع مع نقاطك';

  @override
  String get onboarding3Body =>
      'كل نقطة تكسبها تقرّبك من رتبة أعلى ومزايا أكثر، والصرف لا ينزّل رتبتك.';

  @override
  String get onboarding3ChipA => 'رتبة أعلى';

  @override
  String get onboarding3ChipB => 'مزايا أكثر';

  @override
  String get onboarding4Title => 'عجلة الحظ ومكافآت';

  @override
  String get onboarding4Body =>
      'استبدل نقاطك بلفّة على عجلة الحظ، واربح خصومات وهدايا تُحفظ في مكافآتك.';

  @override
  String get onboarding4ChipA => 'خصومات';

  @override
  String get onboarding4ChipB => 'هدايا';

  @override
  String get onboarding5Title => 'دخول آمن بلا كلمة مرور';

  @override
  String get onboarding5Body =>
      'سجّل برقم جوالك وأكّده برسالة واتساب، ثم افتح التطبيق ببصمتك أو وجهك.';

  @override
  String get onboarding5ChipA => 'واتساب';

  @override
  String get onboarding5ChipB => 'بصمة أو وجه';

  @override
  String get passwordTitle => 'أدخل كلمة المرور';

  @override
  String get passwordSubtitlePrefix => 'للدخول إلى حساب الرقم ';

  @override
  String get passwordLabel => 'كلمة المرور';

  @override
  String get passwordShow => 'إظهار كلمة المرور';

  @override
  String get passwordHide => 'إخفاء كلمة المرور';

  @override
  String get passwordRequired => 'أدخل كلمة المرور.';

  @override
  String get passwordSubmit => 'دخول';

  @override
  String get passwordForgot => 'نسيت كلمة المرور؟ ادخل بكود واتساب';

  @override
  String get setupTitle => 'أكمل بياناتك';

  @override
  String get setupSubtitle =>
      'اختر اسماً وكلمة مرور لتدخل بهما المرة القادمة دون انتظار كود واتساب.';

  @override
  String get setupNameLabel => 'الاسم الكامل';

  @override
  String get setupNameHint => 'مثال: أحمد محمود';

  @override
  String get setupNameRequired => 'أدخل اسمك.';

  @override
  String get setupPasswordLabel => 'كلمة المرور';

  @override
  String get setupPasswordHint => '8 أحرف أو أرقام على الأقل';

  @override
  String get setupPasswordTooShort => 'كلمة المرور يجب ألا تقل عن 8 خانات.';

  @override
  String get setupConfirmLabel => 'تأكيد كلمة المرور';

  @override
  String get setupConfirmMismatch => 'كلمتا المرور غير متطابقتين.';

  @override
  String get setupGovernorateLabel => 'المحافظة';

  @override
  String get setupGovernorateHint => 'اختر محافظتك';

  @override
  String get setupAreaLabel => 'المنطقة';

  @override
  String get setupAreaHint => 'المنطقة أو الحي';

  @override
  String get bindMerchantTitle => 'أدخل كود التاجر';

  @override
  String get bindMerchantSubtitle =>
      'أدخل كود التاجر الذي تعمل معه. يمكنك تغييره لاحقاً من حسابك.';

  @override
  String get bindMerchantCodeLabel => 'كود التاجر';

  @override
  String get bindMerchantCodeHint => 'أدخل كود التاجر';

  @override
  String get bindMerchantSubmit => 'تأكيد';

  @override
  String get bindMerchantCodeRequired => 'أدخل كود التاجر.';

  @override
  String get merchantChangeTitle => 'تغيير التاجر';

  @override
  String get merchantChangeSubtitle =>
      'أدخل كود التاجر الجديد. سيتم مراجعة طلبك من الإدارة.';

  @override
  String get merchantChangeSubmit => 'إرسال الطلب';

  @override
  String get merchantChangeCodeRequired => 'أدخل كود التاجر الجديد.';

  @override
  String get merchantChangePending => 'طلب تغيير التاجر قيد المراجعة.';

  @override
  String get merchantChangeApproved => 'تمت الموافقة على طلب تغيير التاجر.';

  @override
  String get merchantChangeRejected => 'تم رفض طلب تغيير التاجر.';

  @override
  String get merchantChangeRequestSent => 'تم إرسال طلبك وهو قيد المراجعة.';

  @override
  String get setupSave => 'حفظ ومتابعة';

  @override
  String get setupLater => 'لاحقاً';

  @override
  String get setupNote =>
      'الدخول بكود واتساب يبقى متاحاً دائماً إذا نسيت كلمة المرور.';

  @override
  String get resetTitle => 'كلمة مرور جديدة';

  @override
  String get resetSubtitle => 'تم التحقق من رقمك. اختر كلمة مرور جديدة لحسابك.';

  @override
  String get productInStock => 'متوفر';

  @override
  String get productColor => 'اللون: ';

  @override
  String get productDescription => 'الوصف';

  @override
  String get productQrNote =>
      'داخل العبوة كود QR: امسحه بعد الاستلام لتكسب نقاطاً.';

  @override
  String get productAddToCart => 'أضف إلى السلة';

  @override
  String get productAdded => 'تمت إضافة المنتج إلى السلة.';

  @override
  String get productViewCart => 'عرض السلة';

  @override
  String get productLoadFailed => 'تعذر تحميل المنتج';

  @override
  String get productSignInToBuy => 'سجّل الدخول لإضافة المنتجات إلى سلتك.';

  @override
  String get quantityIncrease => 'زيادة الكمية';

  @override
  String get quantityDecrease => 'تقليل الكمية';

  @override
  String get cartTitle => 'السلة';

  @override
  String cartItemsCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count منتج',
      many: '$count منتجاً',
      few: '$count منتجات',
      two: 'منتجان',
      one: 'منتج واحد',
      zero: 'فارغة',
    );
    return '$_temp0';
  }

  @override
  String get cartEmpty => 'سلتك فارغة';

  @override
  String get cartEmptyBody => 'أضف منتجات من المتجر لتظهر هنا.';

  @override
  String get cartBrowse => 'تصفّح المتجر';

  @override
  String get cartGuest => 'سجّل الدخول لعرض سلتك وإتمام طلبك.';

  @override
  String get cartLoadFailed => 'تعذر تحميل السلة';

  @override
  String get cartCouponLabel => 'كود خصم أو مكافأة';

  @override
  String get cartCouponHint => 'أدخل الكود';

  @override
  String get cartCouponApply => 'تطبيق';

  @override
  String get cartCouponRemove => 'إزالة';

  @override
  String get cartCouponApplied => 'تم تطبيق الكود.';

  @override
  String get summarySubtotal => 'المجموع';

  @override
  String get summaryDiscount => 'الخصم';

  @override
  String get summaryShipping => 'الشحن';

  @override
  String get summaryTotal => 'الإجمالي';

  @override
  String get cartCheckout => 'متابعة لإتمام الطلب';

  @override
  String get checkoutTitle => 'إتمام الطلب';

  @override
  String get checkoutAddress => 'عنوان التوصيل';

  @override
  String get checkoutChange => 'تغيير';

  @override
  String get checkoutUseSaved => 'استخدام العنوان المحفوظ';

  @override
  String get addressName => 'اسم المستلم';

  @override
  String get addressPhone => 'رقم الجوال';

  @override
  String get addressGovernorate => 'المحافظة';

  @override
  String get addressCity => 'المدينة';

  @override
  String get addressStreet => 'العنوان بالتفصيل';

  @override
  String get addressStreetHint => 'الشارع، رقم العمارة، الشقة';

  @override
  String get fieldRequired => 'هذا الحقل مطلوب.';

  @override
  String get checkoutPayment => 'طريقة الدفع';

  @override
  String get paymentCod => 'الدفع عند الاستلام';

  @override
  String get paymentOnline => 'بطاقة أو محفظة إلكترونية';

  @override
  String get paymentOnlineBody => 'فيزا، ماستركارد، ميزة، فودافون كاش';

  @override
  String get paymentPoints => 'نقاط الولاء';

  @override
  String paymentPointsBalance(int points) {
    return 'رصيدك $points نقطة';
  }

  @override
  String get checkoutNotes => 'ملاحظات للمندوب';

  @override
  String get checkoutOptional => ' (اختياري)';

  @override
  String get checkoutNotesHint => 'مثال: الاتصال قبل الوصول';

  @override
  String get checkoutConfirm => 'تأكيد الطلب';

  @override
  String get checkoutPaymentOpenFailed =>
      'تم إنشاء الطلب، لكن تعذر فتح صفحة الدفع. يمكنك الدفع لاحقاً من طلباتك.';

  @override
  String get orderPlacedTitle => 'تم استلام طلبك';

  @override
  String get orderPlacedBody => 'سنبدأ تجهيزه الآن، وتصلك إشعارات بكل تحديث.';

  @override
  String get orderPlacedPointsTitle => 'لا تنسَ نقاطك';

  @override
  String get orderPlacedPointsBody =>
      'عند الاستلام، امسح كود QR داخل كل عبوة لتكسب نقاط الولاء.';

  @override
  String get orderPlacedTrack => 'متابعة الطلب';

  @override
  String get orderPlacedHome => 'العودة للرئيسية';

  @override
  String get close => 'إغلاق';

  @override
  String get scanTitle => 'مسح كود QR';

  @override
  String get scanTorchOn => 'تشغيل الفلاش';

  @override
  String get scanTorchOff => 'إطفاء الفلاش';

  @override
  String get scanHint =>
      'اقشط طبقة الحماية داخل العبوة\nووجّه الكاميرا إلى الكود';

  @override
  String get scanCameraUnavailable =>
      'تعذر تشغيل الكاميرا. اسمح للتطبيق باستخدامها من إعدادات الجهاز، أو أدخل الكود يدوياً.';

  @override
  String get scanMerchantQuestion => 'من أي تاجر اشتريت؟';

  @override
  String get scanOptional => 'اختياري';

  @override
  String get scanNoMerchant => 'بدون تاجر';

  @override
  String get scanMerchantCodeHint => 'كود تاجر جديد، مثال M-AB12CD34';

  @override
  String get scanMerchantAdd => 'إضافة';

  @override
  String get scanManualEntry => 'إدخال يدوي';

  @override
  String get scanManualHint => 'الكود المطبوع داخل العبوة';

  @override
  String get scanManualSubmit => 'تأكيد';

  @override
  String get scanResultAdded => 'تمت إضافة';

  @override
  String get scanResultBalance => 'رصيدك الآن';

  @override
  String get scanResultMerchant => 'التاجر';

  @override
  String scanResultToRank(String rank) {
    return 'باقي لرتبة $rank';
  }

  @override
  String get scanAnother => 'مسح كود آخر';

  @override
  String get scanOpenWallet => 'عرض المحفظة';

  @override
  String get paymentTitle => 'دفع آمن';

  @override
  String get paymentChecking => 'جارٍ التأكد من الدفع...';

  @override
  String get paymentLeaveTitle => 'إغلاق صفحة الدفع؟';

  @override
  String get paymentLeaveBody =>
      'طلبك محفوظ. إذا لم تكمل الدفع فلن يبدأ تجهيزه.';

  @override
  String get paymentLeaveStay => 'متابعة الدفع';

  @override
  String get paymentLeaveConfirm => 'إغلاق';

  @override
  String get orderPaidTitle => 'تم الدفع واستلام طلبك';

  @override
  String get orderPaymentPendingBody =>
      'لم نتأكد من الدفع بعد. سنبدأ تجهيز الطلب فور تأكيده.';

  @override
  String get orderPaymentFailedTitle => 'لم يكتمل الدفع';

  @override
  String get orderPaymentFailedBody =>
      'لم يُخصم أي مبلغ. يمكنك إعادة الطلب واختيار طريقة دفع أخرى.';

  @override
  String productLowStock(int count) {
    return 'باقي $count فقط';
  }

  @override
  String get productPerkDelivery => 'توصيل للمنزل';

  @override
  String get productPerkCod => 'الدفع عند الاستلام';

  @override
  String get productPerkPoints => 'نقاط مع كل عبوة';

  @override
  String get productSpecs => 'المواصفات';

  @override
  String get productSpecSku => 'كود المنتج';

  @override
  String get productSpecCategory => 'القسم';

  @override
  String get productSpecWeight => 'الوزن';

  @override
  String get productSpecDimensions => 'الأبعاد';

  @override
  String productWeightKg(String weight) {
    return '$weight كجم';
  }

  @override
  String get productReadMore => 'عرض المزيد';

  @override
  String get productReadLess => 'عرض أقل';

  @override
  String get productSimilar => 'منتجات مشابهة';

  @override
  String productGallery(int current, int total) {
    return 'صورة $current من $total';
  }

  @override
  String get productZoom => 'تكبير الصورة';

  @override
  String productAddWithTotal(String total) {
    return 'أضف إلى السلة · $total';
  }

  @override
  String get lockPinOfferTitle => 'احمِ تطبيقك برمز PIN';

  @override
  String get lockPinOfferBody =>
      'أنشئ رمزاً من 4 أرقام يُطلب عند فتح التطبيق، حتى لا يفتحه غيرك.';

  @override
  String get lockPinPoint1 => 'الرمز يبقى على جهازك فقط، ولا يُرسل إلينا';

  @override
  String get lockPinCreate => 'إنشاء رمز PIN';

  @override
  String get lockUsePinInstead => 'استخدام رمز PIN بدلاً من ذلك';

  @override
  String get pinSetupTitle => 'أنشئ رمز PIN';

  @override
  String get pinSetupBody => 'اختر 4 أرقام تتذكرها بسهولة.';

  @override
  String get pinConfirmTitle => 'أعد إدخال الرمز';

  @override
  String get pinConfirmBody => 'للتأكد من أنك كتبته صحيحاً.';

  @override
  String get pinMismatch => 'الرمزان غير متطابقين. ابدأ من جديد.';

  @override
  String get pinUnlockHint => 'أدخل رمز PIN';

  @override
  String pinWrong(int count) {
    return 'الرمز غير صحيح. المحاولات المتبقية: $count';
  }

  @override
  String get pinBlocked =>
      'تم إيقاف الرمز بعد محاولات خاطئة كثيرة. ادخل بكود واتساب.';

  @override
  String get pinDelete => 'حذف رقم';

  @override
  String pinProgress(int entered, int total) {
    return '$entered من $total أرقام';
  }

  @override
  String get accountAppLock => 'قفل التطبيق';

  @override
  String get accountLockBiometrics => 'بصمة أو وجه';

  @override
  String get accountLockPin => 'رمز PIN';

  @override
  String get orderStatusReceived => 'تم الاستلام';

  @override
  String get orderStatusPreparing => 'جارٍ التجهيز';

  @override
  String get orderStatusShipped => 'تم الشحن';

  @override
  String get orderStatusDelivered => 'تم التسليم';

  @override
  String get orderStatusCancelled => 'ملغي';

  @override
  String get orderAwaitingPayment => 'بانتظار الدفع';

  @override
  String get orderAwaitingPaymentNote =>
      'لم يكتمل دفع هذا الطلب، ولن يبدأ تجهيزه قبل الدفع. إن لم تعد تريده يمكنك إلغاؤه.';

  @override
  String get orderStepReceived => 'الاستلام';

  @override
  String get orderStepPreparing => 'التجهيز';

  @override
  String get orderStepShipping => 'الشحن';

  @override
  String get orderStepDelivery => 'التسليم';

  @override
  String get orderPaymentCod => 'عند الاستلام';

  @override
  String get orderPaymentCard => 'بطاقة';

  @override
  String get orderPaymentPoints => 'نقاط';

  @override
  String get ordersLoadFailed => 'تعذر تحميل الطلبات';

  @override
  String get ordersEmpty => 'لا توجد طلبات بعد';

  @override
  String get ordersEmptyBody => 'طلباتك ستظهر هنا بعد أول عملية شراء.';

  @override
  String get ordersFilterActive => 'جارية';

  @override
  String get ordersFilterCompleted => 'مكتملة';

  @override
  String get ordersNoneInFilter => 'لا توجد طلبات في هذا التصنيف.';

  @override
  String get orderDetailsTitle => 'تفاصيل الطلب';

  @override
  String orderItemQuantity(int count) {
    return 'الكمية $count';
  }

  @override
  String get orderItemFree => 'مجاناً';

  @override
  String get orderCancel => 'إلغاء الطلب';

  @override
  String get orderCancelNote =>
      'متاح قبل الشحن، وترجع نقاطك ومكافأتك تلقائياً.';

  @override
  String get orderCancelConfirmTitle => 'إلغاء هذا الطلب؟';

  @override
  String get orderCancelKeep => 'الإبقاء على الطلب';

  @override
  String get timeNow => 'الآن';

  @override
  String timeMinutesAgo(int count) {
    return 'منذ $count د';
  }

  @override
  String timeHoursAgo(int count) {
    return 'منذ $count س';
  }

  @override
  String get notificationsMarkAllRead => 'تعليم الكل كمقروء';

  @override
  String get notificationsLoadFailed => 'تعذر تحميل الإشعارات';

  @override
  String get notificationsEmpty => 'لا توجد إشعارات';

  @override
  String get notificationsEmptyBody =>
      'تحديثات طلباتك ونقاطك وعروضنا ستظهر هنا.';

  @override
  String get notificationUnread => 'غير مقروء';

  @override
  String accountNewCount(int count) {
    return '$count جديدة';
  }

  @override
  String get view => 'عرض';

  @override
  String get legalTerms => 'الشروط والأحكام';

  @override
  String get legalPrivacy => 'سياسة الخصوصية';

  @override
  String get legalLoadFailed => 'تعذر تحميل الصفحة';

  @override
  String get updateTitle => 'يوجد إصدار جديد';

  @override
  String get updateBody =>
      'هذا الإصدار لم يعد مدعوماً. حدّث التطبيق لتكمل استخدام محفظتك وطلباتك.';

  @override
  String updateVersions(String current, String minimum) {
    return 'إصدارك $current · المطلوب $minimum';
  }

  @override
  String get updateNow => 'تحديث الآن';

  @override
  String get updateStoreFailed =>
      'تعذر فتح المتجر. حدّث التطبيق من Google Play أو App Store.';

  @override
  String get assistantTitle => 'مساعد MAQAM';

  @override
  String get assistantSubtitle => 'مساعد آلي للأسئلة العامة';

  @override
  String get assistantGreeting => 'أهلاً بك! كيف أقدر أساعدك؟';

  @override
  String get assistantTyping => 'المساعد يكتب';

  @override
  String get assistantHint => 'اكتب سؤالك...';

  @override
  String get assistantSend => 'إرسال';

  @override
  String get assistantSuggestPoints => 'إزاي أكسب نقاط؟';

  @override
  String get assistantSuggestCancel => 'إلغاء طلب';

  @override
  String get assistantSuggestReturns => 'سياسة الاسترجاع';

  @override
  String assistantMessagesLeft(int count) {
    return 'متبقي $count رسالة اليوم';
  }

  @override
  String get save => 'حفظ';

  @override
  String get accountEditName => 'تعديل الاسم';

  @override
  String get accountNameLabel => 'الاسم الكامل';

  @override
  String get accountNameRequired => 'اكتب اسمك (حرفان على الأقل).';

  @override
  String get accountNameSaved => 'تم حفظ الاسم.';

  @override
  String get deleteTitle => 'هل أنت متأكد؟';

  @override
  String get deleteSubtitle => 'الحذف نهائي ولا يمكن التراجع عنه.';

  @override
  String get deleteLostTitle => 'سيُحذف نهائياً';

  @override
  String get deleteLostIdentity => 'اسمك ورقم جوالك وبريدك';

  @override
  String get deleteLostPoints => 'رصيد نقاطك ورتبتك';

  @override
  String deleteLostPointsCount(int points) {
    return 'رصيد نقاطك ($points نقطة) ورتبتك';
  }

  @override
  String get deleteLostRewards => 'مكافآتك غير المستخدمة';

  @override
  String get deleteLostAddresses => 'عناوينك وإشعاراتك وسلة مشترياتك';

  @override
  String get deleteKeptTitle => 'يبقى بدون بياناتك الشخصية';

  @override
  String get deleteKeptOrders => 'سجل الطلبات السابقة، لأغراض محاسبية فقط.';

  @override
  String get deleteConfirmPrefix => 'للتأكيد اكتب كلمة ';

  @override
  String get deleteSubmit => 'حذف حسابي نهائياً';

  @override
  String get deleteKeep => 'تراجع';

  @override
  String get deleteDone => 'تم حذف حسابك.';

  @override
  String get suspendedTitle => 'الحساب موقوف مؤقتاً';

  @override
  String get suspendedBody =>
      'رصدنا نشاطاً غير معتاد في عمليات المسح، فأوقفنا الحساب للمراجعة حمايةً لنقاطك. تواصل مع الدعم لإعادة تفعيله.';

  @override
  String get suspendedContact => 'التواصل مع الدعم';

  @override
  String get suspendedSupportMessage =>
      'مرحباً، حسابي في تطبيق MAQAM موقوف وأريد مراجعته.';

  @override
  String get suspendedSupportFailed =>
      'تعذر فتح واتساب. راسلنا على support@maqam-eg.com.';

  @override
  String get changePhoneTitle => 'تغيير رقم الجوال';

  @override
  String get changePhoneSubtitle =>
      'اكتب رقمك الجديد. سنطلب منك إرسال كود من الرقم الجديد على واتساب لإثبات ملكيته.';

  @override
  String get changePhoneLabel => 'رقم الجوال الجديد';

  @override
  String get changePhoneDone => 'تم تغيير رقم الجوال.';

  @override
  String get listSeparator => '، ';

  @override
  String get addressesLoadFailed => 'تعذر تحميل العناوين';

  @override
  String get addressesEmpty =>
      'لا توجد عناوين محفوظة بعد. أضف عنواناً ليظهر عند إتمام الطلب.';

  @override
  String get addressDefault => 'الافتراضي';

  @override
  String get addressEdit => 'تعديل';

  @override
  String get addressMakeDefault => 'تعيين كافتراضي';

  @override
  String get addressDelete => 'حذف';

  @override
  String get addressDeleteTitle => 'حذف العنوان؟';

  @override
  String get addressDeleteBody =>
      'لن يظهر في عناوينك بعد الآن. الطلبات السابقة تحتفظ بعنوانها.';

  @override
  String get addressDeleted => 'تم حذف العنوان.';

  @override
  String get addressSaved => 'تم حفظ العنوان.';

  @override
  String get addressDefaultSet => 'أصبح هذا عنوانك الافتراضي.';

  @override
  String get addressNewTitle => 'عنوان جديد';

  @override
  String get addressEditTitle => 'تعديل العنوان';

  @override
  String get addressRecipient => 'اسم المستلم';

  @override
  String get addressPhoneInvalid => 'رقم غير صحيح';

  @override
  String get addressSave => 'حفظ العنوان';

  @override
  String get addressSaveChanges => 'حفظ التعديلات';

  @override
  String pointsAmount(String points) {
    return '$points نقطة';
  }

  @override
  String get rewardsAvailable => 'متاحة';

  @override
  String get rewardsUsed => 'مستخدمة';

  @override
  String get rewardsExpired => 'منتهية';

  @override
  String get rewardsLoadFailed => 'تعذر تحميل المكافآت';

  @override
  String get rewardsEmpty => 'لا توجد مكافآت هنا';

  @override
  String get rewardsEmptyBody =>
      'جوائز عجلة الحظ والكوبونات الخاصة بك تظهر هنا.';

  @override
  String get rewardsNote =>
      'كود واحد لكل طلب. إذا ألغيت الطلب ترجع المكافأة إلى هنا تلقائياً.';

  @override
  String get rewardsTryWheel => 'جرّب حظك في العجلة';

  @override
  String rewardDiscount(String amount) {
    return 'خصم $amount على طلبك';
  }

  @override
  String rewardProduct(String name) {
    return 'منتج هدية: $name';
  }

  @override
  String get rewardProductFallback => 'منتج';

  @override
  String get rewardFromWheel => 'من عجلة الحظ';

  @override
  String get rewardFromMaqam => 'من MAQAM';

  @override
  String get rewardShipsFree => 'يُشحن مجاناً مع أي طلب';

  @override
  String get rewardNoExpiry => 'بدون تاريخ انتهاء';

  @override
  String rewardExpiresIn(int days) {
    return 'ينتهي خلال $days يوماً';
  }

  @override
  String rewardUsedOn(String date) {
    return 'استُخدم $date';
  }

  @override
  String rewardExpiredOn(String date) {
    return 'انتهى $date';
  }

  @override
  String get rewardUseInCart => 'استخدم في السلة';

  @override
  String get wheelLoadFailed => 'تعذر تحميل العجلة';

  @override
  String get wheelDisabled => 'عجلة الحظ متوقفة حالياً. جرّب لاحقاً.';

  @override
  String wheelSemantics(String slices) {
    return 'عجلة الحظ: $slices';
  }

  @override
  String get wheelCost => 'تكلفة اللفة';

  @override
  String get wheelBalanceAfter => 'رصيدك بعد اللفة';

  @override
  String get wheelSpin => 'لفّ العجلة';

  @override
  String get wheelSpinning => 'العجلة تدور…';

  @override
  String wheelNotEnough(String points) {
    return 'تحتاج $points نقطة على الأقل للّفة.';
  }

  @override
  String get wheelFooterPrefix => 'الجوائز تُضاف إلى ';

  @override
  String get wheelFooterSuffix => ' وتُستخدم بكودها في السلة.';

  @override
  String get wheelWonTitle => 'مبروك!';

  @override
  String wheelWonBody(String prize) {
    return 'ربحت $prize.';
  }

  @override
  String get wheelLostTitle => 'حظ أوفر';

  @override
  String get wheelLostBody => 'لم تربح هذه المرة. جرّب مرة أخرى.';

  @override
  String get wheelOk => 'حسناً';

  @override
  String get merchantDashboardTitle => 'لوحة التاجر';

  @override
  String get merchantLoadFailed => 'تعذر تحميل بيانات التاجر';

  @override
  String get merchantBenefitCode => 'تحصل على كود تاجر خاص بك';

  @override
  String get merchantBenefitPoints =>
      'تكسب نقاطاً كل مرة يمسح عميل كوداً ويختارك';

  @override
  String get merchantBenefitReview =>
      'يراجع فريق MAQAM طلبك ويصلك إشعار بالنتيجة';

  @override
  String get merchantNameLabel => 'اسم النشاط التجاري';

  @override
  String get merchantNameHint => 'مثال: معرض النور';

  @override
  String get merchantAddressLabel => 'عنوان المحل';

  @override
  String get merchantAddressHint => 'المحافظة، المدينة، الشارع';

  @override
  String get merchantLogoLabel => 'شعار المحل';

  @override
  String get merchantLogoPick => 'رفع صورة';

  @override
  String get merchantLogoChosen => 'تم اختيار الصورة';

  @override
  String get merchantLogoHint => 'JPG أو PNG حتى 2MB';

  @override
  String get merchantSubmit => 'إرسال الطلب';

  @override
  String get merchantSubmitNote => 'الكود لا يعمل في المسح قبل موافقة الإدارة.';

  @override
  String get merchantApplied => 'تم إرسال طلبك. سيصلك إشعار بعد المراجعة.';

  @override
  String get merchantApproved => 'تاجر معتمد';

  @override
  String get merchantPending => 'قيد المراجعة';

  @override
  String get merchantCodeLabel => 'كود التاجر';

  @override
  String get merchantShare => 'مشاركة';

  @override
  String merchantShareText(String name, String code) {
    return 'اشتريت من $name؟ أدخل كود التاجر $code عند مسح عبوة MAQAM.';
  }

  @override
  String get merchantCodeCopied => 'تم نسخ كود التاجر.';

  @override
  String get merchantCodeHint =>
      'اطلب من العميل إدخال هذا الكود عند مسح أي عبوة اشتراها منك.';

  @override
  String get merchantPendingHint =>
      'طلبك قيد المراجعة. سيعمل الكود بعد موافقة الإدارة ويصلك إشعار بذلك.';

  @override
  String get merchantLogoPlaceholder => 'الشعار';

  @override
  String get merchantChangeLogo => 'تغيير شعار المحل';

  @override
  String get merchantStatPoints => 'إجمالي نقاطك';

  @override
  String get merchantStatScans => 'عدد المسحات';

  @override
  String get merchantStatMonth => 'مسحات هذا الشهر';

  @override
  String get merchantStatCustomers => 'عملاء مختلفون';

  @override
  String get merchantPointsNote =>
      'نقاط التاجر رصيد تراكمي للعرض حالياً، ولا يمكن استخدام كودك في مسحك أنت.';

  @override
  String get scanOfflineBanner => 'لا يوجد اتصال: المسح يُحفظ على هاتفك الآن';

  @override
  String get scanOfflineSaved => 'تم حفظ المسح';

  @override
  String get scanOfflineExplain =>
      'نقاطك لم تُضف بعد. سنرفع المسحات تلقائياً ونضيف النقاط أول ما يرجع الإنترنت، حتى لو كان التطبيق مغلقاً.';

  @override
  String scanCodeTail(String tail) {
    return 'الكود $tail ••••';
  }

  @override
  String get scanPendingBadge => 'في الانتظار';

  @override
  String scanPendingCount(int count) {
    return 'في الانتظار ($count)';
  }

  @override
  String get pendingTitle => 'مسحات بدون إنترنت';

  @override
  String get pendingEmpty => 'لا توجد مسحات في الانتظار';

  @override
  String get pendingEmptyBody =>
      'المسحات التي تتم بدون إنترنت تُحفظ هنا حتى تُرفع.';

  @override
  String pendingCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count مسحة في انتظار الرفع',
      few: '$count مسحات في انتظار الرفع',
      two: 'مسحتان في انتظار الرفع',
      one: 'مسحة واحدة في انتظار الرفع',
    );
    return '$_temp0';
  }

  @override
  String get pendingAuto => 'تُرفع تلقائياً عند رجوع الاتصال';

  @override
  String get pendingUploadNow => 'رفع الآن';

  @override
  String get pendingStillOffline =>
      'لا يوجد اتصال بعد. سنحاول مرة أخرى تلقائياً.';

  @override
  String get pendingWaiting => 'في الانتظار';

  @override
  String get pendingLastUpload => 'آخر عملية رفع';

  @override
  String pendingRejected(String reason) {
    return 'لم يُقبل: $reason';
  }

  @override
  String get pendingAlreadyCredited => 'أُضيف';

  @override
  String get pendingHide => 'إخفاء';

  @override
  String get pendingFooter =>
      'المسحات محفوظة على هذا الهاتف فقط. لا تحذف التطبيق قبل رفعها.';

  @override
  String get addressStreetDetail => 'الشارع، رقم العمارة، الدور';

  @override
  String get wheelLocked => 'عجلة الحظ مقفلة';

  @override
  String get seminarTitle => 'السيمينار';

  @override
  String get seminarLoadFailed => 'تعذر تحميل السيمينار';

  @override
  String get seminarLive => 'جارٍ الآن';

  @override
  String get seminarEnded => 'انتهى';

  @override
  String seminarEndsIn(String time) {
    return 'ينتهي خلال $time';
  }

  @override
  String seminarDays(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count يوماً',
      few: '$count أيام',
      two: 'يومين',
      one: 'يوم',
    );
    return '$_temp0';
  }

  @override
  String seminarHours(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count ساعة',
      few: '$count ساعات',
      two: 'ساعتين',
      one: 'ساعة',
    );
    return '$_temp0';
  }

  @override
  String seminarMinutes(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count دقيقة',
      few: '$count دقائق',
      two: 'دقيقتين',
      one: 'دقيقة',
    );
    return '$_temp0';
  }

  @override
  String seminarPromoBody(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'سحب على $count جائزة',
      few: 'سحب على $count جوائز',
      two: 'سحب على جائزتين',
      one: 'سحب على جائزة',
    );
    return '$_temp0';
  }

  @override
  String seminarRegisteredCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count مسجل',
      few: '$count مسجلين',
      two: 'مسجلان',
      one: 'مسجل واحد',
      zero: 'كن أول المسجلين',
    );
    return '$_temp0';
  }

  @override
  String get seminarJoin => 'سجّل في السحب';

  @override
  String get seminarYouAreIn => 'أنت مسجّل';

  @override
  String get seminarRegisteredDone => 'تم تسجيلك في السحب. بالتوفيق!';

  @override
  String get seminarRegisteredLive => 'أنت مسجّل في السحب. تابع النتائج هنا.';

  @override
  String get seminarRegisteredEnded => 'كنت مسجّلاً في هذا السحب.';

  @override
  String get seminarClosed => 'انتهى التسجيل في هذا السيمينار.';

  @override
  String get seminarHowItWorks =>
      'سجّل أثناء السيمينار، ويُسحب الفائزون عشوائياً. كل شخص يفوز بجائزة واحدة على الأكثر.';

  @override
  String get seminarPrizes => 'الجوائز والفائزون';

  @override
  String get seminarNotDrawn => 'لم تُسحب بعد';

  @override
  String seminarWinner(String name) {
    return 'الفائز: $name';
  }

  @override
  String get seminarWinnerYou => 'الفائز: أنت!';

  @override
  String seminarYouWon(String prize) {
    return 'مبروك! فزت بـ $prize';
  }

  @override
  String get seminarCollect => 'تواصل مع فريق MAQAM لاستلام جائزتك.';

  @override
  String get lockWithFaceId => 'بـ Face ID';

  @override
  String get lockWithFace => 'ببصمة الوجه';

  @override
  String get lockWithFingerprint => 'ببصمة الإصبع';

  @override
  String biometricSetupBodyWith(String method) {
    return 'ادخل $method بدل كتابة رقمك وكلمة المرور، حتى بعد تسجيل الخروج.';
  }

  @override
  String biometricSetupEnableWith(String method) {
    return 'تفعيل الدخول $method';
  }

  @override
  String unlockButtonWith(String method) {
    return 'الدخول $method';
  }

  @override
  String get unlockHintFace => 'انظر إلى الهاتف للدخول';

  @override
  String get unlockHintFingerprint => 'المس مستشعر البصمة للدخول';

  @override
  String get unlockBrowseAsGuest => 'تصفّح كزائر';

  @override
  String get merchantRecentScans => 'آخر من استخدم كودك';

  @override
  String get merchantViewAll => 'عرض الكل';

  @override
  String get merchantNoScans =>
      'لم يستخدم أحد كودك بعد. شاركه مع عملائك ليبدأ رصيدك.';

  @override
  String get merchantScansTitle => 'المسحات بكودك';

  @override
  String get merchantScansLoadFailed => 'تعذر تحميل المسحات';

  @override
  String merchantScanPoints(int points) {
    return '+$points نقطة';
  }

  @override
  String get merchantStatMonthPoints => 'نقاط هذا الشهر';

  @override
  String get merchantStatTotalScans => 'إجمالي المسحات';

  @override
  String get merchantStatTotalPoints => 'إجمالي النقاط';

  @override
  String get merchantDashboard => 'لوحة تحكم التاجر';

  @override
  String get merchantTechnicians => 'الفنيين';

  @override
  String get merchantTechnicianScans => 'السكانات';

  @override
  String get merchantTechnicianPoints => 'النقاط';

  @override
  String get merchantTechnicianBalance => 'الرصيد';

  @override
  String get merchantDeductPoints => 'خصم نقاط';

  @override
  String get merchantDeductAmount => 'المبلغ';

  @override
  String get merchantDeductAmountHint => 'عدد النقاط المراد خصمها';

  @override
  String get merchantDeductReason => 'السبب';

  @override
  String get merchantDeductReasonHint => 'مثال: مرتجع منتج';

  @override
  String get merchantDeductSubmit => 'خصم';

  @override
  String get merchantDeductSuccess => 'تم خصم النقاط بنجاح.';

  @override
  String get merchantDeductAmountRequired => 'أدخل المبلغ.';

  @override
  String get merchantDeductReasonRequired => 'أدخل السبب.';

  @override
  String get merchantTransactions => 'العمليات';

  @override
  String get merchantTransactionEarned => 'إضافة';

  @override
  String get merchantTransactionDeducted => 'خصم';

  @override
  String get merchantNoTechnicians => 'لا يوجد فنيين مربوطين بتاجرك بعد.';

  @override
  String get merchantNoTransactions => 'لا توجد عمليات بعد.';

  @override
  String get merchantLoadMore => 'تحميل المزيد';

  @override
  String get merchantUnknownCustomer => 'عميل';

  @override
  String merchantSince(String date) {
    return 'تاجر منذ $date';
  }

  @override
  String merchantLastScan(String time) {
    return 'آخر مسح: $time';
  }

  @override
  String get accountPointsBalance => 'رصيد النقاط';

  @override
  String get accountRank => 'المستوى';

  @override
  String accountToNextRank(String points, String rank) {
    return '$points نقطة للوصول إلى $rank';
  }

  @override
  String get accountTopRank => 'وصلت لأعلى مستوى';

  @override
  String get accountMerchantOpen => 'لوحة التاجر';

  @override
  String get accountMerchantPendingTitle => 'طلب التاجر قيد المراجعة';

  @override
  String get accountCopyCode => 'نسخ الكود';
}
