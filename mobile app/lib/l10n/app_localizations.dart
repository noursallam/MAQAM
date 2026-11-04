import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// No description provided for @appName.
  ///
  /// In ar, this message translates to:
  /// **'مقام'**
  String get appName;

  /// No description provided for @onboardingStart.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ الآن'**
  String get onboardingStart;

  /// No description provided for @onboardingGuest.
  ///
  /// In ar, this message translates to:
  /// **'تصفّح المتجر كزائر'**
  String get onboardingGuest;

  /// No description provided for @loginTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get loginTitle;

  /// No description provided for @loginSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم جوالك. بدون كلمة مرور: ستؤكد الرقم برسالة قصيرة من واتساب.'**
  String get loginSubtitle;

  /// No description provided for @loginPhoneLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الجوال'**
  String get loginPhoneLabel;

  /// No description provided for @loginContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة'**
  String get loginContinue;

  /// No description provided for @loginNoSpam.
  ///
  /// In ar, this message translates to:
  /// **'لن نرسل لك أي رسالة قبل أن تراسلنا أنت أولاً.'**
  String get loginNoSpam;

  /// No description provided for @loginPhoneRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم الجوال.'**
  String get loginPhoneRequired;

  /// No description provided for @loginPhoneInvalid.
  ///
  /// In ar, this message translates to:
  /// **'رقم الجوال غير صحيح. مثال: 10 1234 5678'**
  String get loginPhoneInvalid;

  /// No description provided for @loginLegalPrefix.
  ///
  /// In ar, this message translates to:
  /// **'بالمتابعة توافق على '**
  String get loginLegalPrefix;

  /// No description provided for @loginLegalTerms.
  ///
  /// In ar, this message translates to:
  /// **'الشروط والأحكام'**
  String get loginLegalTerms;

  /// No description provided for @loginLegalAnd.
  ///
  /// In ar, this message translates to:
  /// **' و'**
  String get loginLegalAnd;

  /// No description provided for @loginLegalPrivacy.
  ///
  /// In ar, this message translates to:
  /// **'سياسة الخصوصية'**
  String get loginLegalPrivacy;

  /// No description provided for @switchLanguage.
  ///
  /// In ar, this message translates to:
  /// **'English'**
  String get switchLanguage;

  /// No description provided for @back.
  ///
  /// In ar, this message translates to:
  /// **'رجوع'**
  String get back;

  /// No description provided for @catalogAll.
  ///
  /// In ar, this message translates to:
  /// **'الكل'**
  String get catalogAll;

  /// No description provided for @catalogLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل المنتجات'**
  String get catalogLoadFailed;

  /// No description provided for @catalogEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد منتجات'**
  String get catalogEmpty;

  /// No description provided for @retry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get retry;

  /// No description provided for @priceOnRequest.
  ///
  /// In ar, this message translates to:
  /// **'السعر عند الطلب'**
  String get priceOnRequest;

  /// No description provided for @priceEgp.
  ///
  /// In ar, this message translates to:
  /// **'{amount} ج.م'**
  String priceEgp(String amount);

  /// No description provided for @stepOf.
  ///
  /// In ar, this message translates to:
  /// **'الخطوة {step} من {total}'**
  String stepOf(int step, int total);

  /// No description provided for @sendCodeTitle.
  ///
  /// In ar, this message translates to:
  /// **'أرسل الكود من واتساب'**
  String get sendCodeTitle;

  /// No description provided for @sendCodeSubtitlePrefix.
  ///
  /// In ar, this message translates to:
  /// **'أرسله من الرقم '**
  String get sendCodeSubtitlePrefix;

  /// No description provided for @sendCodeSubtitleSuffix.
  ///
  /// In ar, this message translates to:
  /// **' وسنرد عليك بكود الدخول.'**
  String get sendCodeSubtitleSuffix;

  /// No description provided for @sendCodeLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود التأكيد'**
  String get sendCodeLabel;

  /// No description provided for @sendCodeCopy.
  ///
  /// In ar, this message translates to:
  /// **'نسخ الكود'**
  String get sendCodeCopy;

  /// No description provided for @sendCodeCopied.
  ///
  /// In ar, this message translates to:
  /// **'تم نسخ الكود'**
  String get sendCodeCopied;

  /// No description provided for @sendCodeOpenWhatsapp.
  ///
  /// In ar, this message translates to:
  /// **'فتح واتساب وإرسال الكود'**
  String get sendCodeOpenWhatsapp;

  /// No description provided for @sendCodeWhatsappMissing.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فتح واتساب. انسخ الكود وأرسله يدوياً.'**
  String get sendCodeWhatsappMissing;

  /// No description provided for @sendCodeWaiting.
  ///
  /// In ar, this message translates to:
  /// **'في انتظار رسالتك...'**
  String get sendCodeWaiting;

  /// No description provided for @sendCodeStep1.
  ///
  /// In ar, this message translates to:
  /// **'اضغط الزر: الرسالة مكتوبة جاهزة'**
  String get sendCodeStep1;

  /// No description provided for @sendCodeStep2.
  ///
  /// In ar, this message translates to:
  /// **'اضغط «إرسال» داخل واتساب'**
  String get sendCodeStep2;

  /// No description provided for @sendCodeStep3.
  ///
  /// In ar, this message translates to:
  /// **'ارجع للتطبيق واكتب كود الدخول الذي يصلك'**
  String get sendCodeStep3;

  /// No description provided for @sendCodeValidFor.
  ///
  /// In ar, this message translates to:
  /// **'الكود صالح لمدة {minutes} دقائق · '**
  String sendCodeValidFor(int minutes);

  /// No description provided for @sendCodeChangeNumber.
  ///
  /// In ar, this message translates to:
  /// **'تغيير الرقم'**
  String get sendCodeChangeNumber;

  /// No description provided for @sendCodeExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية الكود.'**
  String get sendCodeExpired;

  /// No description provided for @startOver.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ من جديد'**
  String get startOver;

  /// No description provided for @otpTitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود الدخول'**
  String get otpTitle;

  /// No description provided for @otpSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'وصلتنا رسالتك، وأرسلنا لك الكود على واتساب.'**
  String get otpSubtitle;

  /// No description provided for @otpFieldLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود الدخول المكوّن من 6 أرقام'**
  String get otpFieldLabel;

  /// No description provided for @otpExpiresIn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي الكود خلال '**
  String get otpExpiresIn;

  /// No description provided for @otpExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية كود الدخول.'**
  String get otpExpired;

  /// No description provided for @otpSubmit.
  ///
  /// In ar, this message translates to:
  /// **'دخول'**
  String get otpSubmit;

  /// No description provided for @otpNotReceivedPrefix.
  ///
  /// In ar, this message translates to:
  /// **'لم يصلك الكود؟ تأكد أنك أرسلت من نفس الرقم، ثم '**
  String get otpNotReceivedPrefix;

  /// No description provided for @otpDoNotShare.
  ///
  /// In ar, this message translates to:
  /// **'لا تشارك هذا الكود مع أي شخص. فريق MAQAM لن يطلبه منك أبداً.'**
  String get otpDoNotShare;

  /// No description provided for @errorNetwork.
  ///
  /// In ar, this message translates to:
  /// **'تعذر الاتصال. تحقق من الإنترنت وحاول مرة أخرى.'**
  String get errorNetwork;

  /// No description provided for @errorGeneric.
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ غير متوقع. حاول مرة أخرى.'**
  String get errorGeneric;

  /// No description provided for @signIn.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get signIn;

  /// No description provided for @signOut.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get signOut;

  /// No description provided for @skip.
  ///
  /// In ar, this message translates to:
  /// **'تخطي'**
  String get skip;

  /// No description provided for @biometricSetupTitle.
  ///
  /// In ar, this message translates to:
  /// **'ادخل أسرع المرة القادمة'**
  String get biometricSetupTitle;

  /// No description provided for @biometricSetupPoint1.
  ///
  /// In ar, this message translates to:
  /// **'بصمتك تبقى على جهازك فقط، ولا تُرسل إلينا'**
  String get biometricSetupPoint1;

  /// No description provided for @biometricSetupPoint2.
  ///
  /// In ar, this message translates to:
  /// **'تقدر توقفه في أي وقت من «حسابي»'**
  String get biometricSetupPoint2;

  /// No description provided for @biometricSetupPoint3.
  ///
  /// In ar, this message translates to:
  /// **'الدخول بكود واتساب يبقى متاحاً دائماً'**
  String get biometricSetupPoint3;

  /// No description provided for @biometricSetupNotNow.
  ///
  /// In ar, this message translates to:
  /// **'ليس الآن'**
  String get biometricSetupNotNow;

  /// No description provided for @biometricSetupFailed.
  ///
  /// In ar, this message translates to:
  /// **'لم نتمكن من التحقق. حاول مرة أخرى أو اختر «ليس الآن».'**
  String get biometricSetupFailed;

  /// No description provided for @biometricEnableReason.
  ///
  /// In ar, this message translates to:
  /// **'أكّد هويتك لتفعيل الدخول بالبصمة أو الوجه'**
  String get biometricEnableReason;

  /// No description provided for @unlockWelcomeBack.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً بعودتك'**
  String get unlockWelcomeBack;

  /// No description provided for @unlockWelcomeBackName.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً بعودتك، {name}'**
  String unlockWelcomeBackName(String name);

  /// No description provided for @unlockFailed.
  ///
  /// In ar, this message translates to:
  /// **'لم نتمكن من التحقق. حاول مرة أخرى.'**
  String get unlockFailed;

  /// No description provided for @unlockReason.
  ///
  /// In ar, this message translates to:
  /// **'افتح تطبيق مقام'**
  String get unlockReason;

  /// No description provided for @unlockUseWhatsapp.
  ///
  /// In ar, this message translates to:
  /// **'الدخول بكود واتساب'**
  String get unlockUseWhatsapp;

  /// No description provided for @unlockDifferentAccount.
  ///
  /// In ar, this message translates to:
  /// **'ليس حسابك؟ دخول برقم آخر'**
  String get unlockDifferentAccount;

  /// No description provided for @navHome.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get navHome;

  /// No description provided for @navStore.
  ///
  /// In ar, this message translates to:
  /// **'المتجر'**
  String get navStore;

  /// No description provided for @navScan.
  ///
  /// In ar, this message translates to:
  /// **'مسح كود'**
  String get navScan;

  /// No description provided for @navWallet.
  ///
  /// In ar, this message translates to:
  /// **'المحفظة'**
  String get navWallet;

  /// No description provided for @navAccount.
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get navAccount;

  /// No description provided for @homeGreeting.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً {name} · رصيد نقاطك'**
  String homeGreeting(String name);

  /// No description provided for @homeGreetingNoName.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً بك · رصيد نقاطك'**
  String get homeGreetingNoName;

  /// No description provided for @pointsUnit.
  ///
  /// In ar, this message translates to:
  /// **'نقطة'**
  String get pointsUnit;

  /// No description provided for @homeToNextRank.
  ///
  /// In ar, this message translates to:
  /// **'باقي {points} نقطة للوصول إلى رتبة {rank}'**
  String homeToNextRank(int points, String rank);

  /// No description provided for @homeTopRank.
  ///
  /// In ar, this message translates to:
  /// **'أنت في أعلى رتبة'**
  String get homeTopRank;

  /// No description provided for @rankUpCongrats.
  ///
  /// In ar, this message translates to:
  /// **'مبروك!'**
  String get rankUpCongrats;

  /// No description provided for @rankUpMessage.
  ///
  /// In ar, this message translates to:
  /// **'لقد تمت ترقيتك إلى رتبة {rank}'**
  String rankUpMessage(String rank);

  /// No description provided for @rankUpDismiss.
  ///
  /// In ar, this message translates to:
  /// **'تمام'**
  String get rankUpDismiss;

  /// No description provided for @homeGuestTitle.
  ///
  /// In ar, this message translates to:
  /// **'اكسب نقاطاً مع كل عبوة'**
  String get homeGuestTitle;

  /// No description provided for @homeGuestBody.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول لتمسح الأكواد وتجمع النقاط والجوائز.'**
  String get homeGuestBody;

  /// No description provided for @actionScan.
  ///
  /// In ar, this message translates to:
  /// **'مسح كود'**
  String get actionScan;

  /// No description provided for @actionWheel.
  ///
  /// In ar, this message translates to:
  /// **'عجلة الحظ'**
  String get actionWheel;

  /// No description provided for @actionRewards.
  ///
  /// In ar, this message translates to:
  /// **'مكافآتي'**
  String get actionRewards;

  /// No description provided for @actionOrders.
  ///
  /// In ar, this message translates to:
  /// **'طلباتي'**
  String get actionOrders;

  /// No description provided for @actionLeaderboard.
  ///
  /// In ar, this message translates to:
  /// **'المتصدرين'**
  String get actionLeaderboard;

  /// No description provided for @leaderboardTitle.
  ///
  /// In ar, this message translates to:
  /// **'لوحة المتصدرين'**
  String get leaderboardTitle;

  /// No description provided for @leaderboardEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد متصدرين بعد'**
  String get leaderboardEmpty;

  /// No description provided for @leaderboardPoints.
  ///
  /// In ar, this message translates to:
  /// **'{points} نقطة'**
  String leaderboardPoints(String points);

  /// No description provided for @homeNewArrivals.
  ///
  /// In ar, this message translates to:
  /// **'وصل حديثاً'**
  String get homeNewArrivals;

  /// No description provided for @viewAll.
  ///
  /// In ar, this message translates to:
  /// **'عرض الكل'**
  String get viewAll;

  /// No description provided for @chat.
  ///
  /// In ar, this message translates to:
  /// **'المساعد'**
  String get chat;

  /// No description provided for @notifications.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات'**
  String get notifications;

  /// No description provided for @storeSearchHint.
  ///
  /// In ar, this message translates to:
  /// **'ابحث باسم المنتج أو الكود'**
  String get storeSearchHint;

  /// No description provided for @storeCount.
  ///
  /// In ar, this message translates to:
  /// **'{count} منتج'**
  String storeCount(int count);

  /// No description provided for @cart.
  ///
  /// In ar, this message translates to:
  /// **'السلة'**
  String get cart;

  /// No description provided for @sortNewest.
  ///
  /// In ar, this message translates to:
  /// **'الأحدث'**
  String get sortNewest;

  /// No description provided for @sortPriceLow.
  ///
  /// In ar, this message translates to:
  /// **'السعر: من الأقل'**
  String get sortPriceLow;

  /// No description provided for @sortPriceHigh.
  ///
  /// In ar, this message translates to:
  /// **'السعر: من الأعلى'**
  String get sortPriceHigh;

  /// No description provided for @sortBy.
  ///
  /// In ar, this message translates to:
  /// **'الترتيب'**
  String get sortBy;

  /// No description provided for @outOfStock.
  ///
  /// In ar, this message translates to:
  /// **'غير متوفر'**
  String get outOfStock;

  /// No description provided for @walletAvailable.
  ///
  /// In ar, this message translates to:
  /// **'متاح للصرف'**
  String get walletAvailable;

  /// No description provided for @walletTotalEarned.
  ///
  /// In ar, this message translates to:
  /// **'إجمالي ما كسبته'**
  String get walletTotalEarned;

  /// No description provided for @walletNextRankAt.
  ///
  /// In ar, this message translates to:
  /// **'{rank} عند {points}'**
  String walletNextRankAt(String rank, int points);

  /// No description provided for @walletRankNote.
  ///
  /// In ar, this message translates to:
  /// **'الرتبة تُحسب على إجمالي ما كسبته: الصرف لا ينزّل رتبتك.'**
  String get walletRankNote;

  /// No description provided for @walletEarn.
  ///
  /// In ar, this message translates to:
  /// **'اكسب نقاطاً'**
  String get walletEarn;

  /// No description provided for @walletRecent.
  ///
  /// In ar, this message translates to:
  /// **'آخر الحركات'**
  String get walletRecent;

  /// No description provided for @filterEarned.
  ///
  /// In ar, this message translates to:
  /// **'كسب'**
  String get filterEarned;

  /// No description provided for @filterSpent.
  ///
  /// In ar, this message translates to:
  /// **'صرف'**
  String get filterSpent;

  /// No description provided for @walletNoTransactions.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد حركات بعد'**
  String get walletNoTransactions;

  /// No description provided for @walletLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل المحفظة'**
  String get walletLoadFailed;

  /// No description provided for @walletGuest.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول لعرض نقاطك وحركاتك.'**
  String get walletGuest;

  /// No description provided for @today.
  ///
  /// In ar, this message translates to:
  /// **'اليوم'**
  String get today;

  /// No description provided for @yesterday.
  ///
  /// In ar, this message translates to:
  /// **'أمس'**
  String get yesterday;

  /// No description provided for @accountEdit.
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get accountEdit;

  /// No description provided for @accountMerchantTitle.
  ///
  /// In ar, this message translates to:
  /// **'الانضمام كتاجر معتمد'**
  String get accountMerchantTitle;

  /// No description provided for @accountMerchantBody.
  ///
  /// In ar, this message translates to:
  /// **'اكسب نقاطاً مع كل مسح بكودك'**
  String get accountMerchantBody;

  /// No description provided for @accountAddresses.
  ///
  /// In ar, this message translates to:
  /// **'عناويني'**
  String get accountAddresses;

  /// No description provided for @accountPendingScans.
  ///
  /// In ar, this message translates to:
  /// **'مسحات في انتظار الرفع'**
  String get accountPendingScans;

  /// No description provided for @accountBiometricsOff.
  ///
  /// In ar, this message translates to:
  /// **'غير مفعّل'**
  String get accountBiometricsOff;

  /// No description provided for @accountChangePhone.
  ///
  /// In ar, this message translates to:
  /// **'تغيير رقم الجوال'**
  String get accountChangePhone;

  /// No description provided for @accountLanguage.
  ///
  /// In ar, this message translates to:
  /// **'اللغة'**
  String get accountLanguage;

  /// No description provided for @languageName.
  ///
  /// In ar, this message translates to:
  /// **'العربية'**
  String get languageName;

  /// No description provided for @accountHelp.
  ///
  /// In ar, this message translates to:
  /// **'المساعد والأسئلة الشائعة'**
  String get accountHelp;

  /// No description provided for @accountLegal.
  ///
  /// In ar, this message translates to:
  /// **'الشروط وسياسة الخصوصية'**
  String get accountLegal;

  /// No description provided for @accountDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف الحساب'**
  String get accountDelete;

  /// No description provided for @accountVersion.
  ///
  /// In ar, this message translates to:
  /// **'الإصدار {version}'**
  String accountVersion(String version);

  /// No description provided for @accountGuest.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول لإدارة حسابك وطلباتك.'**
  String get accountGuest;

  /// No description provided for @accountNoName.
  ///
  /// In ar, this message translates to:
  /// **'عميل مقام'**
  String get accountNoName;

  /// No description provided for @signOutConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج؟'**
  String get signOutConfirmTitle;

  /// No description provided for @signOutConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'ستحتاج إلى كود واتساب للدخول مرة أخرى.'**
  String get signOutConfirmBody;

  /// No description provided for @cancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get cancel;

  /// No description provided for @onboardingNext.
  ///
  /// In ar, this message translates to:
  /// **'التالي'**
  String get onboardingNext;

  /// No description provided for @onboardingPage.
  ///
  /// In ar, this message translates to:
  /// **'الشاشة {current} من {total}'**
  String onboardingPage(int current, int total);

  /// No description provided for @onboarding1Title.
  ///
  /// In ar, this message translates to:
  /// **'الفخامة في كل لمسة'**
  String get onboarding1Title;

  /// No description provided for @onboarding1Body.
  ///
  /// In ar, this message translates to:
  /// **'تسوّق مفاتيح وبرايز وإضاءة MAQAM، واطلبها إلى باب بيتك.'**
  String get onboarding1Body;

  /// No description provided for @onboarding1ChipA.
  ///
  /// In ar, this message translates to:
  /// **'المتجر'**
  String get onboarding1ChipA;

  /// No description provided for @onboarding1ChipB.
  ///
  /// In ar, this message translates to:
  /// **'توصيل للمنزل'**
  String get onboarding1ChipB;

  /// No description provided for @onboarding2Title.
  ///
  /// In ar, this message translates to:
  /// **'امسح واكسب'**
  String get onboarding2Title;

  /// No description provided for @onboarding2Body.
  ///
  /// In ar, this message translates to:
  /// **'امسح الكود الموجود على كل عبوة لتضيف نقاطاً إلى رصيدك فوراً.'**
  String get onboarding2Body;

  /// No description provided for @onboarding2ChipA.
  ///
  /// In ar, this message translates to:
  /// **'كود العبوة'**
  String get onboarding2ChipA;

  /// No description provided for @onboarding2ChipB.
  ///
  /// In ar, this message translates to:
  /// **'نقاط فورية'**
  String get onboarding2ChipB;

  /// No description provided for @onboarding3Title.
  ///
  /// In ar, this message translates to:
  /// **'رتبتك ترتفع مع نقاطك'**
  String get onboarding3Title;

  /// No description provided for @onboarding3Body.
  ///
  /// In ar, this message translates to:
  /// **'كل نقطة تكسبها تقرّبك من رتبة أعلى ومزايا أكثر، والصرف لا ينزّل رتبتك.'**
  String get onboarding3Body;

  /// No description provided for @onboarding3ChipA.
  ///
  /// In ar, this message translates to:
  /// **'رتبة أعلى'**
  String get onboarding3ChipA;

  /// No description provided for @onboarding3ChipB.
  ///
  /// In ar, this message translates to:
  /// **'مزايا أكثر'**
  String get onboarding3ChipB;

  /// No description provided for @onboarding4Title.
  ///
  /// In ar, this message translates to:
  /// **'عجلة الحظ ومكافآت'**
  String get onboarding4Title;

  /// No description provided for @onboarding4Body.
  ///
  /// In ar, this message translates to:
  /// **'استبدل نقاطك بلفّة على عجلة الحظ، واربح خصومات وهدايا تُحفظ في مكافآتك.'**
  String get onboarding4Body;

  /// No description provided for @onboarding4ChipA.
  ///
  /// In ar, this message translates to:
  /// **'خصومات'**
  String get onboarding4ChipA;

  /// No description provided for @onboarding4ChipB.
  ///
  /// In ar, this message translates to:
  /// **'هدايا'**
  String get onboarding4ChipB;

  /// No description provided for @onboarding5Title.
  ///
  /// In ar, this message translates to:
  /// **'دخول آمن بلا كلمة مرور'**
  String get onboarding5Title;

  /// No description provided for @onboarding5Body.
  ///
  /// In ar, this message translates to:
  /// **'سجّل برقم جوالك وأكّده برسالة واتساب، ثم افتح التطبيق ببصمتك أو وجهك.'**
  String get onboarding5Body;

  /// No description provided for @onboarding5ChipA.
  ///
  /// In ar, this message translates to:
  /// **'واتساب'**
  String get onboarding5ChipA;

  /// No description provided for @onboarding5ChipB.
  ///
  /// In ar, this message translates to:
  /// **'بصمة أو وجه'**
  String get onboarding5ChipB;

  /// No description provided for @passwordTitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كلمة المرور'**
  String get passwordTitle;

  /// No description provided for @passwordSubtitlePrefix.
  ///
  /// In ar, this message translates to:
  /// **'للدخول إلى حساب الرقم '**
  String get passwordSubtitlePrefix;

  /// No description provided for @passwordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور'**
  String get passwordLabel;

  /// No description provided for @passwordShow.
  ///
  /// In ar, this message translates to:
  /// **'إظهار كلمة المرور'**
  String get passwordShow;

  /// No description provided for @passwordHide.
  ///
  /// In ar, this message translates to:
  /// **'إخفاء كلمة المرور'**
  String get passwordHide;

  /// No description provided for @passwordRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كلمة المرور.'**
  String get passwordRequired;

  /// No description provided for @passwordSubmit.
  ///
  /// In ar, this message translates to:
  /// **'دخول'**
  String get passwordSubmit;

  /// No description provided for @passwordForgot.
  ///
  /// In ar, this message translates to:
  /// **'نسيت كلمة المرور؟ ادخل بكود واتساب'**
  String get passwordForgot;

  /// No description provided for @setupTitle.
  ///
  /// In ar, this message translates to:
  /// **'أكمل بياناتك'**
  String get setupTitle;

  /// No description provided for @setupSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'اختر اسماً وكلمة مرور لتدخل بهما المرة القادمة دون انتظار كود واتساب.'**
  String get setupSubtitle;

  /// No description provided for @setupNameLabel.
  ///
  /// In ar, this message translates to:
  /// **'الاسم الكامل'**
  String get setupNameLabel;

  /// No description provided for @setupNameHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: أحمد محمود'**
  String get setupNameHint;

  /// No description provided for @setupNameRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل اسمك.'**
  String get setupNameRequired;

  /// No description provided for @setupPasswordLabel.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور'**
  String get setupPasswordLabel;

  /// No description provided for @setupPasswordHint.
  ///
  /// In ar, this message translates to:
  /// **'8 أحرف أو أرقام على الأقل'**
  String get setupPasswordHint;

  /// No description provided for @setupPasswordTooShort.
  ///
  /// In ar, this message translates to:
  /// **'كلمة المرور يجب ألا تقل عن 8 خانات.'**
  String get setupPasswordTooShort;

  /// No description provided for @setupConfirmLabel.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد كلمة المرور'**
  String get setupConfirmLabel;

  /// No description provided for @setupConfirmMismatch.
  ///
  /// In ar, this message translates to:
  /// **'كلمتا المرور غير متطابقتين.'**
  String get setupConfirmMismatch;

  /// No description provided for @setupGovernorateLabel.
  ///
  /// In ar, this message translates to:
  /// **'المحافظة'**
  String get setupGovernorateLabel;

  /// No description provided for @setupGovernorateHint.
  ///
  /// In ar, this message translates to:
  /// **'اختر محافظتك'**
  String get setupGovernorateHint;

  /// No description provided for @setupAreaLabel.
  ///
  /// In ar, this message translates to:
  /// **'المنطقة'**
  String get setupAreaLabel;

  /// No description provided for @setupAreaHint.
  ///
  /// In ar, this message translates to:
  /// **'المنطقة أو الحي'**
  String get setupAreaHint;

  /// No description provided for @bindMerchantTitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر'**
  String get bindMerchantTitle;

  /// No description provided for @bindMerchantSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر الذي تعمل معه. يمكنك تغييره لاحقاً من حسابك.'**
  String get bindMerchantSubtitle;

  /// No description provided for @bindMerchantCodeLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود التاجر'**
  String get bindMerchantCodeLabel;

  /// No description provided for @bindMerchantCodeHint.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر'**
  String get bindMerchantCodeHint;

  /// No description provided for @bindMerchantSubmit.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد'**
  String get bindMerchantSubmit;

  /// No description provided for @bindMerchantCodeRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر.'**
  String get bindMerchantCodeRequired;

  /// No description provided for @merchantChangeTitle.
  ///
  /// In ar, this message translates to:
  /// **'تغيير التاجر'**
  String get merchantChangeTitle;

  /// No description provided for @merchantChangeSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر الجديد. سيتم مراجعة طلبك من الإدارة.'**
  String get merchantChangeSubtitle;

  /// No description provided for @merchantChangeSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get merchantChangeSubmit;

  /// No description provided for @merchantChangeCodeRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل كود التاجر الجديد.'**
  String get merchantChangeCodeRequired;

  /// No description provided for @merchantChangePending.
  ///
  /// In ar, this message translates to:
  /// **'طلب تغيير التاجر قيد المراجعة.'**
  String get merchantChangePending;

  /// No description provided for @merchantChangeApproved.
  ///
  /// In ar, this message translates to:
  /// **'تمت الموافقة على طلب تغيير التاجر.'**
  String get merchantChangeApproved;

  /// No description provided for @merchantChangeRejected.
  ///
  /// In ar, this message translates to:
  /// **'تم رفض طلب تغيير التاجر.'**
  String get merchantChangeRejected;

  /// No description provided for @merchantChangeRequestSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك وهو قيد المراجعة.'**
  String get merchantChangeRequestSent;

  /// No description provided for @setupSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ ومتابعة'**
  String get setupSave;

  /// No description provided for @setupLater.
  ///
  /// In ar, this message translates to:
  /// **'لاحقاً'**
  String get setupLater;

  /// No description provided for @setupNote.
  ///
  /// In ar, this message translates to:
  /// **'الدخول بكود واتساب يبقى متاحاً دائماً إذا نسيت كلمة المرور.'**
  String get setupNote;

  /// No description provided for @resetTitle.
  ///
  /// In ar, this message translates to:
  /// **'كلمة مرور جديدة'**
  String get resetTitle;

  /// No description provided for @resetSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'تم التحقق من رقمك. اختر كلمة مرور جديدة لحسابك.'**
  String get resetSubtitle;

  /// No description provided for @productInStock.
  ///
  /// In ar, this message translates to:
  /// **'متوفر'**
  String get productInStock;

  /// No description provided for @productColor.
  ///
  /// In ar, this message translates to:
  /// **'اللون: '**
  String get productColor;

  /// No description provided for @productDescription.
  ///
  /// In ar, this message translates to:
  /// **'الوصف'**
  String get productDescription;

  /// No description provided for @productQrNote.
  ///
  /// In ar, this message translates to:
  /// **'داخل العبوة كود QR: امسحه بعد الاستلام لتكسب نقاطاً.'**
  String get productQrNote;

  /// No description provided for @productAddToCart.
  ///
  /// In ar, this message translates to:
  /// **'أضف إلى السلة'**
  String get productAddToCart;

  /// No description provided for @productAdded.
  ///
  /// In ar, this message translates to:
  /// **'تمت إضافة المنتج إلى السلة.'**
  String get productAdded;

  /// No description provided for @productViewCart.
  ///
  /// In ar, this message translates to:
  /// **'عرض السلة'**
  String get productViewCart;

  /// No description provided for @productLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل المنتج'**
  String get productLoadFailed;

  /// No description provided for @productSignInToBuy.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول لإضافة المنتجات إلى سلتك.'**
  String get productSignInToBuy;

  /// No description provided for @quantityIncrease.
  ///
  /// In ar, this message translates to:
  /// **'زيادة الكمية'**
  String get quantityIncrease;

  /// No description provided for @quantityDecrease.
  ///
  /// In ar, this message translates to:
  /// **'تقليل الكمية'**
  String get quantityDecrease;

  /// No description provided for @cartTitle.
  ///
  /// In ar, this message translates to:
  /// **'السلة'**
  String get cartTitle;

  /// No description provided for @cartItemsCount.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =0{فارغة} =1{منتج واحد} =2{منتجان} few{{count} منتجات} many{{count} منتجاً} other{{count} منتج}}'**
  String cartItemsCount(int count);

  /// No description provided for @cartEmpty.
  ///
  /// In ar, this message translates to:
  /// **'سلتك فارغة'**
  String get cartEmpty;

  /// No description provided for @cartEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'أضف منتجات من المتجر لتظهر هنا.'**
  String get cartEmptyBody;

  /// No description provided for @cartBrowse.
  ///
  /// In ar, this message translates to:
  /// **'تصفّح المتجر'**
  String get cartBrowse;

  /// No description provided for @cartGuest.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول لعرض سلتك وإتمام طلبك.'**
  String get cartGuest;

  /// No description provided for @cartLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل السلة'**
  String get cartLoadFailed;

  /// No description provided for @cartCouponLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود خصم أو مكافأة'**
  String get cartCouponLabel;

  /// No description provided for @cartCouponHint.
  ///
  /// In ar, this message translates to:
  /// **'أدخل الكود'**
  String get cartCouponHint;

  /// No description provided for @cartCouponApply.
  ///
  /// In ar, this message translates to:
  /// **'تطبيق'**
  String get cartCouponApply;

  /// No description provided for @cartCouponRemove.
  ///
  /// In ar, this message translates to:
  /// **'إزالة'**
  String get cartCouponRemove;

  /// No description provided for @cartCouponApplied.
  ///
  /// In ar, this message translates to:
  /// **'تم تطبيق الكود.'**
  String get cartCouponApplied;

  /// No description provided for @summarySubtotal.
  ///
  /// In ar, this message translates to:
  /// **'المجموع'**
  String get summarySubtotal;

  /// No description provided for @summaryDiscount.
  ///
  /// In ar, this message translates to:
  /// **'الخصم'**
  String get summaryDiscount;

  /// No description provided for @summaryShipping.
  ///
  /// In ar, this message translates to:
  /// **'الشحن'**
  String get summaryShipping;

  /// No description provided for @summaryTotal.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي'**
  String get summaryTotal;

  /// No description provided for @cartCheckout.
  ///
  /// In ar, this message translates to:
  /// **'متابعة لإتمام الطلب'**
  String get cartCheckout;

  /// No description provided for @checkoutTitle.
  ///
  /// In ar, this message translates to:
  /// **'إتمام الطلب'**
  String get checkoutTitle;

  /// No description provided for @checkoutAddress.
  ///
  /// In ar, this message translates to:
  /// **'عنوان التوصيل'**
  String get checkoutAddress;

  /// No description provided for @checkoutChange.
  ///
  /// In ar, this message translates to:
  /// **'تغيير'**
  String get checkoutChange;

  /// No description provided for @checkoutUseSaved.
  ///
  /// In ar, this message translates to:
  /// **'استخدام العنوان المحفوظ'**
  String get checkoutUseSaved;

  /// No description provided for @addressName.
  ///
  /// In ar, this message translates to:
  /// **'اسم المستلم'**
  String get addressName;

  /// No description provided for @addressPhone.
  ///
  /// In ar, this message translates to:
  /// **'رقم الجوال'**
  String get addressPhone;

  /// No description provided for @addressGovernorate.
  ///
  /// In ar, this message translates to:
  /// **'المحافظة'**
  String get addressGovernorate;

  /// No description provided for @addressCity.
  ///
  /// In ar, this message translates to:
  /// **'المدينة'**
  String get addressCity;

  /// No description provided for @addressStreet.
  ///
  /// In ar, this message translates to:
  /// **'العنوان بالتفصيل'**
  String get addressStreet;

  /// No description provided for @addressStreetHint.
  ///
  /// In ar, this message translates to:
  /// **'الشارع، رقم العمارة، الشقة'**
  String get addressStreetHint;

  /// No description provided for @fieldRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب.'**
  String get fieldRequired;

  /// No description provided for @checkoutPayment.
  ///
  /// In ar, this message translates to:
  /// **'طريقة الدفع'**
  String get checkoutPayment;

  /// No description provided for @paymentCod.
  ///
  /// In ar, this message translates to:
  /// **'الدفع عند الاستلام'**
  String get paymentCod;

  /// No description provided for @paymentOnline.
  ///
  /// In ar, this message translates to:
  /// **'بطاقة أو محفظة إلكترونية'**
  String get paymentOnline;

  /// No description provided for @paymentOnlineBody.
  ///
  /// In ar, this message translates to:
  /// **'فيزا، ماستركارد، ميزة، فودافون كاش'**
  String get paymentOnlineBody;

  /// No description provided for @paymentPoints.
  ///
  /// In ar, this message translates to:
  /// **'نقاط الولاء'**
  String get paymentPoints;

  /// No description provided for @paymentPointsBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيدك {points} نقطة'**
  String paymentPointsBalance(int points);

  /// No description provided for @checkoutNotes.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات للمندوب'**
  String get checkoutNotes;

  /// No description provided for @checkoutOptional.
  ///
  /// In ar, this message translates to:
  /// **' (اختياري)'**
  String get checkoutOptional;

  /// No description provided for @checkoutNotesHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: الاتصال قبل الوصول'**
  String get checkoutNotesHint;

  /// No description provided for @checkoutConfirm.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الطلب'**
  String get checkoutConfirm;

  /// No description provided for @checkoutPaymentOpenFailed.
  ///
  /// In ar, this message translates to:
  /// **'تم إنشاء الطلب، لكن تعذر فتح صفحة الدفع. يمكنك الدفع لاحقاً من طلباتك.'**
  String get checkoutPaymentOpenFailed;

  /// No description provided for @orderPlacedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم استلام طلبك'**
  String get orderPlacedTitle;

  /// No description provided for @orderPlacedBody.
  ///
  /// In ar, this message translates to:
  /// **'سنبدأ تجهيزه الآن، وتصلك إشعارات بكل تحديث.'**
  String get orderPlacedBody;

  /// No description provided for @orderPlacedPointsTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا تنسَ نقاطك'**
  String get orderPlacedPointsTitle;

  /// No description provided for @orderPlacedPointsBody.
  ///
  /// In ar, this message translates to:
  /// **'عند الاستلام، امسح كود QR داخل كل عبوة لتكسب نقاط الولاء.'**
  String get orderPlacedPointsBody;

  /// No description provided for @orderPlacedTrack.
  ///
  /// In ar, this message translates to:
  /// **'متابعة الطلب'**
  String get orderPlacedTrack;

  /// No description provided for @orderPlacedHome.
  ///
  /// In ar, this message translates to:
  /// **'العودة للرئيسية'**
  String get orderPlacedHome;

  /// No description provided for @close.
  ///
  /// In ar, this message translates to:
  /// **'إغلاق'**
  String get close;

  /// No description provided for @scanTitle.
  ///
  /// In ar, this message translates to:
  /// **'مسح كود QR'**
  String get scanTitle;

  /// No description provided for @scanTorchOn.
  ///
  /// In ar, this message translates to:
  /// **'تشغيل الفلاش'**
  String get scanTorchOn;

  /// No description provided for @scanTorchOff.
  ///
  /// In ar, this message translates to:
  /// **'إطفاء الفلاش'**
  String get scanTorchOff;

  /// No description provided for @scanHint.
  ///
  /// In ar, this message translates to:
  /// **'اقشط طبقة الحماية داخل العبوة\nووجّه الكاميرا إلى الكود'**
  String get scanHint;

  /// No description provided for @scanCameraUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تشغيل الكاميرا. اسمح للتطبيق باستخدامها من إعدادات الجهاز، أو أدخل الكود يدوياً.'**
  String get scanCameraUnavailable;

  /// No description provided for @scanMerchantQuestion.
  ///
  /// In ar, this message translates to:
  /// **'من أي تاجر اشتريت؟'**
  String get scanMerchantQuestion;

  /// No description provided for @scanOptional.
  ///
  /// In ar, this message translates to:
  /// **'اختياري'**
  String get scanOptional;

  /// No description provided for @scanNoMerchant.
  ///
  /// In ar, this message translates to:
  /// **'بدون تاجر'**
  String get scanNoMerchant;

  /// No description provided for @scanMerchantCodeHint.
  ///
  /// In ar, this message translates to:
  /// **'كود تاجر جديد، مثال M-AB12CD34'**
  String get scanMerchantCodeHint;

  /// No description provided for @scanMerchantAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة'**
  String get scanMerchantAdd;

  /// No description provided for @scanManualEntry.
  ///
  /// In ar, this message translates to:
  /// **'إدخال يدوي'**
  String get scanManualEntry;

  /// No description provided for @scanManualHint.
  ///
  /// In ar, this message translates to:
  /// **'الكود المطبوع داخل العبوة'**
  String get scanManualHint;

  /// No description provided for @scanManualSubmit.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد'**
  String get scanManualSubmit;

  /// No description provided for @scanResultAdded.
  ///
  /// In ar, this message translates to:
  /// **'تمت إضافة'**
  String get scanResultAdded;

  /// No description provided for @scanResultBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيدك الآن'**
  String get scanResultBalance;

  /// No description provided for @scanResultMerchant.
  ///
  /// In ar, this message translates to:
  /// **'التاجر'**
  String get scanResultMerchant;

  /// No description provided for @scanResultToRank.
  ///
  /// In ar, this message translates to:
  /// **'باقي لرتبة {rank}'**
  String scanResultToRank(String rank);

  /// No description provided for @scanAnother.
  ///
  /// In ar, this message translates to:
  /// **'مسح كود آخر'**
  String get scanAnother;

  /// No description provided for @scanOpenWallet.
  ///
  /// In ar, this message translates to:
  /// **'عرض المحفظة'**
  String get scanOpenWallet;

  /// No description provided for @paymentTitle.
  ///
  /// In ar, this message translates to:
  /// **'دفع آمن'**
  String get paymentTitle;

  /// No description provided for @paymentChecking.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التأكد من الدفع...'**
  String get paymentChecking;

  /// No description provided for @paymentLeaveTitle.
  ///
  /// In ar, this message translates to:
  /// **'إغلاق صفحة الدفع؟'**
  String get paymentLeaveTitle;

  /// No description provided for @paymentLeaveBody.
  ///
  /// In ar, this message translates to:
  /// **'طلبك محفوظ. إذا لم تكمل الدفع فلن يبدأ تجهيزه.'**
  String get paymentLeaveBody;

  /// No description provided for @paymentLeaveStay.
  ///
  /// In ar, this message translates to:
  /// **'متابعة الدفع'**
  String get paymentLeaveStay;

  /// No description provided for @paymentLeaveConfirm.
  ///
  /// In ar, this message translates to:
  /// **'إغلاق'**
  String get paymentLeaveConfirm;

  /// No description provided for @orderPaidTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم الدفع واستلام طلبك'**
  String get orderPaidTitle;

  /// No description provided for @orderPaymentPendingBody.
  ///
  /// In ar, this message translates to:
  /// **'لم نتأكد من الدفع بعد. سنبدأ تجهيز الطلب فور تأكيده.'**
  String get orderPaymentPendingBody;

  /// No description provided for @orderPaymentFailedTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم يكتمل الدفع'**
  String get orderPaymentFailedTitle;

  /// No description provided for @orderPaymentFailedBody.
  ///
  /// In ar, this message translates to:
  /// **'لم يُخصم أي مبلغ. يمكنك إعادة الطلب واختيار طريقة دفع أخرى.'**
  String get orderPaymentFailedBody;

  /// No description provided for @productLowStock.
  ///
  /// In ar, this message translates to:
  /// **'باقي {count} فقط'**
  String productLowStock(int count);

  /// No description provided for @productPerkDelivery.
  ///
  /// In ar, this message translates to:
  /// **'توصيل للمنزل'**
  String get productPerkDelivery;

  /// No description provided for @productPerkCod.
  ///
  /// In ar, this message translates to:
  /// **'الدفع عند الاستلام'**
  String get productPerkCod;

  /// No description provided for @productPerkPoints.
  ///
  /// In ar, this message translates to:
  /// **'نقاط مع كل عبوة'**
  String get productPerkPoints;

  /// No description provided for @productSpecs.
  ///
  /// In ar, this message translates to:
  /// **'المواصفات'**
  String get productSpecs;

  /// No description provided for @productSpecSku.
  ///
  /// In ar, this message translates to:
  /// **'كود المنتج'**
  String get productSpecSku;

  /// No description provided for @productSpecCategory.
  ///
  /// In ar, this message translates to:
  /// **'القسم'**
  String get productSpecCategory;

  /// No description provided for @productSpecWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن'**
  String get productSpecWeight;

  /// No description provided for @productSpecDimensions.
  ///
  /// In ar, this message translates to:
  /// **'الأبعاد'**
  String get productSpecDimensions;

  /// No description provided for @productWeightKg.
  ///
  /// In ar, this message translates to:
  /// **'{weight} كجم'**
  String productWeightKg(String weight);

  /// No description provided for @productReadMore.
  ///
  /// In ar, this message translates to:
  /// **'عرض المزيد'**
  String get productReadMore;

  /// No description provided for @productReadLess.
  ///
  /// In ar, this message translates to:
  /// **'عرض أقل'**
  String get productReadLess;

  /// No description provided for @productSimilar.
  ///
  /// In ar, this message translates to:
  /// **'منتجات مشابهة'**
  String get productSimilar;

  /// No description provided for @productGallery.
  ///
  /// In ar, this message translates to:
  /// **'صورة {current} من {total}'**
  String productGallery(int current, int total);

  /// No description provided for @productZoom.
  ///
  /// In ar, this message translates to:
  /// **'تكبير الصورة'**
  String get productZoom;

  /// No description provided for @productAddWithTotal.
  ///
  /// In ar, this message translates to:
  /// **'أضف إلى السلة · {total}'**
  String productAddWithTotal(String total);

  /// No description provided for @lockPinOfferTitle.
  ///
  /// In ar, this message translates to:
  /// **'احمِ تطبيقك برمز PIN'**
  String get lockPinOfferTitle;

  /// No description provided for @lockPinOfferBody.
  ///
  /// In ar, this message translates to:
  /// **'أنشئ رمزاً من 4 أرقام يُطلب عند فتح التطبيق، حتى لا يفتحه غيرك.'**
  String get lockPinOfferBody;

  /// No description provided for @lockPinPoint1.
  ///
  /// In ar, this message translates to:
  /// **'الرمز يبقى على جهازك فقط، ولا يُرسل إلينا'**
  String get lockPinPoint1;

  /// No description provided for @lockPinCreate.
  ///
  /// In ar, this message translates to:
  /// **'إنشاء رمز PIN'**
  String get lockPinCreate;

  /// No description provided for @lockUsePinInstead.
  ///
  /// In ar, this message translates to:
  /// **'استخدام رمز PIN بدلاً من ذلك'**
  String get lockUsePinInstead;

  /// No description provided for @pinSetupTitle.
  ///
  /// In ar, this message translates to:
  /// **'أنشئ رمز PIN'**
  String get pinSetupTitle;

  /// No description provided for @pinSetupBody.
  ///
  /// In ar, this message translates to:
  /// **'اختر 4 أرقام تتذكرها بسهولة.'**
  String get pinSetupBody;

  /// No description provided for @pinConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'أعد إدخال الرمز'**
  String get pinConfirmTitle;

  /// No description provided for @pinConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'للتأكد من أنك كتبته صحيحاً.'**
  String get pinConfirmBody;

  /// No description provided for @pinMismatch.
  ///
  /// In ar, this message translates to:
  /// **'الرمزان غير متطابقين. ابدأ من جديد.'**
  String get pinMismatch;

  /// No description provided for @pinUnlockHint.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رمز PIN'**
  String get pinUnlockHint;

  /// No description provided for @pinWrong.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح. المحاولات المتبقية: {count}'**
  String pinWrong(int count);

  /// No description provided for @pinBlocked.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف الرمز بعد محاولات خاطئة كثيرة. ادخل بكود واتساب.'**
  String get pinBlocked;

  /// No description provided for @pinDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف رقم'**
  String get pinDelete;

  /// No description provided for @pinProgress.
  ///
  /// In ar, this message translates to:
  /// **'{entered} من {total} أرقام'**
  String pinProgress(int entered, int total);

  /// No description provided for @accountAppLock.
  ///
  /// In ar, this message translates to:
  /// **'قفل التطبيق'**
  String get accountAppLock;

  /// No description provided for @accountLockBiometrics.
  ///
  /// In ar, this message translates to:
  /// **'بصمة أو وجه'**
  String get accountLockBiometrics;

  /// No description provided for @accountLockPin.
  ///
  /// In ar, this message translates to:
  /// **'رمز PIN'**
  String get accountLockPin;

  /// No description provided for @orderStatusReceived.
  ///
  /// In ar, this message translates to:
  /// **'تم الاستلام'**
  String get orderStatusReceived;

  /// No description provided for @orderStatusPreparing.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التجهيز'**
  String get orderStatusPreparing;

  /// No description provided for @orderStatusShipped.
  ///
  /// In ar, this message translates to:
  /// **'تم الشحن'**
  String get orderStatusShipped;

  /// No description provided for @orderStatusDelivered.
  ///
  /// In ar, this message translates to:
  /// **'تم التسليم'**
  String get orderStatusDelivered;

  /// No description provided for @orderStatusCancelled.
  ///
  /// In ar, this message translates to:
  /// **'ملغي'**
  String get orderStatusCancelled;

  /// No description provided for @orderAwaitingPayment.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار الدفع'**
  String get orderAwaitingPayment;

  /// No description provided for @orderAwaitingPaymentNote.
  ///
  /// In ar, this message translates to:
  /// **'لم يكتمل دفع هذا الطلب، ولن يبدأ تجهيزه قبل الدفع. إن لم تعد تريده يمكنك إلغاؤه.'**
  String get orderAwaitingPaymentNote;

  /// No description provided for @orderStepReceived.
  ///
  /// In ar, this message translates to:
  /// **'الاستلام'**
  String get orderStepReceived;

  /// No description provided for @orderStepPreparing.
  ///
  /// In ar, this message translates to:
  /// **'التجهيز'**
  String get orderStepPreparing;

  /// No description provided for @orderStepShipping.
  ///
  /// In ar, this message translates to:
  /// **'الشحن'**
  String get orderStepShipping;

  /// No description provided for @orderStepDelivery.
  ///
  /// In ar, this message translates to:
  /// **'التسليم'**
  String get orderStepDelivery;

  /// No description provided for @orderPaymentCod.
  ///
  /// In ar, this message translates to:
  /// **'عند الاستلام'**
  String get orderPaymentCod;

  /// No description provided for @orderPaymentCard.
  ///
  /// In ar, this message translates to:
  /// **'بطاقة'**
  String get orderPaymentCard;

  /// No description provided for @orderPaymentPoints.
  ///
  /// In ar, this message translates to:
  /// **'نقاط'**
  String get orderPaymentPoints;

  /// No description provided for @ordersLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الطلبات'**
  String get ordersLoadFailed;

  /// No description provided for @ordersEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد طلبات بعد'**
  String get ordersEmpty;

  /// No description provided for @ordersEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'طلباتك ستظهر هنا بعد أول عملية شراء.'**
  String get ordersEmptyBody;

  /// No description provided for @ordersFilterActive.
  ///
  /// In ar, this message translates to:
  /// **'جارية'**
  String get ordersFilterActive;

  /// No description provided for @ordersFilterCompleted.
  ///
  /// In ar, this message translates to:
  /// **'مكتملة'**
  String get ordersFilterCompleted;

  /// No description provided for @ordersNoneInFilter.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد طلبات في هذا التصنيف.'**
  String get ordersNoneInFilter;

  /// No description provided for @orderDetailsTitle.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الطلب'**
  String get orderDetailsTitle;

  /// No description provided for @orderItemQuantity.
  ///
  /// In ar, this message translates to:
  /// **'الكمية {count}'**
  String orderItemQuantity(int count);

  /// No description provided for @orderItemFree.
  ///
  /// In ar, this message translates to:
  /// **'مجاناً'**
  String get orderItemFree;

  /// No description provided for @orderCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الطلب'**
  String get orderCancel;

  /// No description provided for @orderCancelNote.
  ///
  /// In ar, this message translates to:
  /// **'متاح قبل الشحن، وترجع نقاطك ومكافأتك تلقائياً.'**
  String get orderCancelNote;

  /// No description provided for @orderCancelConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء هذا الطلب؟'**
  String get orderCancelConfirmTitle;

  /// No description provided for @orderCancelKeep.
  ///
  /// In ar, this message translates to:
  /// **'الإبقاء على الطلب'**
  String get orderCancelKeep;

  /// No description provided for @timeNow.
  ///
  /// In ar, this message translates to:
  /// **'الآن'**
  String get timeNow;

  /// No description provided for @timeMinutesAgo.
  ///
  /// In ar, this message translates to:
  /// **'منذ {count} د'**
  String timeMinutesAgo(int count);

  /// No description provided for @timeHoursAgo.
  ///
  /// In ar, this message translates to:
  /// **'منذ {count} س'**
  String timeHoursAgo(int count);

  /// No description provided for @notificationsMarkAllRead.
  ///
  /// In ar, this message translates to:
  /// **'تعليم الكل كمقروء'**
  String get notificationsMarkAllRead;

  /// No description provided for @notificationsLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الإشعارات'**
  String get notificationsLoadFailed;

  /// No description provided for @notificationsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إشعارات'**
  String get notificationsEmpty;

  /// No description provided for @notificationsEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'تحديثات طلباتك ونقاطك وعروضنا ستظهر هنا.'**
  String get notificationsEmptyBody;

  /// No description provided for @notificationUnread.
  ///
  /// In ar, this message translates to:
  /// **'غير مقروء'**
  String get notificationUnread;

  /// No description provided for @accountNewCount.
  ///
  /// In ar, this message translates to:
  /// **'{count} جديدة'**
  String accountNewCount(int count);

  /// No description provided for @view.
  ///
  /// In ar, this message translates to:
  /// **'عرض'**
  String get view;

  /// No description provided for @legalTerms.
  ///
  /// In ar, this message translates to:
  /// **'الشروط والأحكام'**
  String get legalTerms;

  /// No description provided for @legalPrivacy.
  ///
  /// In ar, this message translates to:
  /// **'سياسة الخصوصية'**
  String get legalPrivacy;

  /// No description provided for @legalLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الصفحة'**
  String get legalLoadFailed;

  /// No description provided for @updateTitle.
  ///
  /// In ar, this message translates to:
  /// **'يوجد إصدار جديد'**
  String get updateTitle;

  /// No description provided for @updateBody.
  ///
  /// In ar, this message translates to:
  /// **'هذا الإصدار لم يعد مدعوماً. حدّث التطبيق لتكمل استخدام محفظتك وطلباتك.'**
  String get updateBody;

  /// No description provided for @updateVersions.
  ///
  /// In ar, this message translates to:
  /// **'إصدارك {current} · المطلوب {minimum}'**
  String updateVersions(String current, String minimum);

  /// No description provided for @updateNow.
  ///
  /// In ar, this message translates to:
  /// **'تحديث الآن'**
  String get updateNow;

  /// No description provided for @updateStoreFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فتح المتجر. حدّث التطبيق من Google Play أو App Store.'**
  String get updateStoreFailed;

  /// No description provided for @assistantTitle.
  ///
  /// In ar, this message translates to:
  /// **'مساعد MAQAM'**
  String get assistantTitle;

  /// No description provided for @assistantSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'مساعد آلي للأسئلة العامة'**
  String get assistantSubtitle;

  /// No description provided for @assistantGreeting.
  ///
  /// In ar, this message translates to:
  /// **'أهلاً بك! كيف أقدر أساعدك؟'**
  String get assistantGreeting;

  /// No description provided for @assistantTyping.
  ///
  /// In ar, this message translates to:
  /// **'المساعد يكتب'**
  String get assistantTyping;

  /// No description provided for @assistantHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتب سؤالك...'**
  String get assistantHint;

  /// No description provided for @assistantSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get assistantSend;

  /// No description provided for @assistantSuggestPoints.
  ///
  /// In ar, this message translates to:
  /// **'إزاي أكسب نقاط؟'**
  String get assistantSuggestPoints;

  /// No description provided for @assistantSuggestCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء طلب'**
  String get assistantSuggestCancel;

  /// No description provided for @assistantSuggestReturns.
  ///
  /// In ar, this message translates to:
  /// **'سياسة الاسترجاع'**
  String get assistantSuggestReturns;

  /// No description provided for @assistantMessagesLeft.
  ///
  /// In ar, this message translates to:
  /// **'متبقي {count} رسالة اليوم'**
  String assistantMessagesLeft(int count);

  /// No description provided for @save.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get save;

  /// No description provided for @accountEditName.
  ///
  /// In ar, this message translates to:
  /// **'تعديل الاسم'**
  String get accountEditName;

  /// No description provided for @accountNameLabel.
  ///
  /// In ar, this message translates to:
  /// **'الاسم الكامل'**
  String get accountNameLabel;

  /// No description provided for @accountNameRequired.
  ///
  /// In ar, this message translates to:
  /// **'اكتب اسمك (حرفان على الأقل).'**
  String get accountNameRequired;

  /// No description provided for @accountNameSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ الاسم.'**
  String get accountNameSaved;

  /// No description provided for @deleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'هل أنت متأكد؟'**
  String get deleteTitle;

  /// No description provided for @deleteSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'الحذف نهائي ولا يمكن التراجع عنه.'**
  String get deleteSubtitle;

  /// No description provided for @deleteLostTitle.
  ///
  /// In ar, this message translates to:
  /// **'سيُحذف نهائياً'**
  String get deleteLostTitle;

  /// No description provided for @deleteLostIdentity.
  ///
  /// In ar, this message translates to:
  /// **'اسمك ورقم جوالك وبريدك'**
  String get deleteLostIdentity;

  /// No description provided for @deleteLostPoints.
  ///
  /// In ar, this message translates to:
  /// **'رصيد نقاطك ورتبتك'**
  String get deleteLostPoints;

  /// No description provided for @deleteLostPointsCount.
  ///
  /// In ar, this message translates to:
  /// **'رصيد نقاطك ({points} نقطة) ورتبتك'**
  String deleteLostPointsCount(int points);

  /// No description provided for @deleteLostRewards.
  ///
  /// In ar, this message translates to:
  /// **'مكافآتك غير المستخدمة'**
  String get deleteLostRewards;

  /// No description provided for @deleteLostAddresses.
  ///
  /// In ar, this message translates to:
  /// **'عناوينك وإشعاراتك وسلة مشترياتك'**
  String get deleteLostAddresses;

  /// No description provided for @deleteKeptTitle.
  ///
  /// In ar, this message translates to:
  /// **'يبقى بدون بياناتك الشخصية'**
  String get deleteKeptTitle;

  /// No description provided for @deleteKeptOrders.
  ///
  /// In ar, this message translates to:
  /// **'سجل الطلبات السابقة، لأغراض محاسبية فقط.'**
  String get deleteKeptOrders;

  /// No description provided for @deleteConfirmPrefix.
  ///
  /// In ar, this message translates to:
  /// **'للتأكيد اكتب كلمة '**
  String get deleteConfirmPrefix;

  /// No description provided for @deleteSubmit.
  ///
  /// In ar, this message translates to:
  /// **'حذف حسابي نهائياً'**
  String get deleteSubmit;

  /// No description provided for @deleteKeep.
  ///
  /// In ar, this message translates to:
  /// **'تراجع'**
  String get deleteKeep;

  /// No description provided for @deleteDone.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف حسابك.'**
  String get deleteDone;

  /// No description provided for @suspendedTitle.
  ///
  /// In ar, this message translates to:
  /// **'الحساب موقوف مؤقتاً'**
  String get suspendedTitle;

  /// No description provided for @suspendedBody.
  ///
  /// In ar, this message translates to:
  /// **'رصدنا نشاطاً غير معتاد في عمليات المسح، فأوقفنا الحساب للمراجعة حمايةً لنقاطك. تواصل مع الدعم لإعادة تفعيله.'**
  String get suspendedBody;

  /// No description provided for @suspendedContact.
  ///
  /// In ar, this message translates to:
  /// **'التواصل مع الدعم'**
  String get suspendedContact;

  /// No description provided for @suspendedSupportMessage.
  ///
  /// In ar, this message translates to:
  /// **'مرحباً، حسابي في تطبيق MAQAM موقوف وأريد مراجعته.'**
  String get suspendedSupportMessage;

  /// No description provided for @suspendedSupportFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فتح واتساب. راسلنا على support@maqam-eg.com.'**
  String get suspendedSupportFailed;

  /// No description provided for @changePhoneTitle.
  ///
  /// In ar, this message translates to:
  /// **'تغيير رقم الجوال'**
  String get changePhoneTitle;

  /// No description provided for @changePhoneSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'اكتب رقمك الجديد. سنطلب منك إرسال كود من الرقم الجديد على واتساب لإثبات ملكيته.'**
  String get changePhoneSubtitle;

  /// No description provided for @changePhoneLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الجوال الجديد'**
  String get changePhoneLabel;

  /// No description provided for @changePhoneDone.
  ///
  /// In ar, this message translates to:
  /// **'تم تغيير رقم الجوال.'**
  String get changePhoneDone;

  /// No description provided for @listSeparator.
  ///
  /// In ar, this message translates to:
  /// **'، '**
  String get listSeparator;

  /// No description provided for @addressesLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل العناوين'**
  String get addressesLoadFailed;

  /// No description provided for @addressesEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عناوين محفوظة بعد. أضف عنواناً ليظهر عند إتمام الطلب.'**
  String get addressesEmpty;

  /// No description provided for @addressDefault.
  ///
  /// In ar, this message translates to:
  /// **'الافتراضي'**
  String get addressDefault;

  /// No description provided for @addressEdit.
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get addressEdit;

  /// No description provided for @addressMakeDefault.
  ///
  /// In ar, this message translates to:
  /// **'تعيين كافتراضي'**
  String get addressMakeDefault;

  /// No description provided for @addressDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get addressDelete;

  /// No description provided for @addressDeleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف العنوان؟'**
  String get addressDeleteTitle;

  /// No description provided for @addressDeleteBody.
  ///
  /// In ar, this message translates to:
  /// **'لن يظهر في عناوينك بعد الآن. الطلبات السابقة تحتفظ بعنوانها.'**
  String get addressDeleteBody;

  /// No description provided for @addressDeleted.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف العنوان.'**
  String get addressDeleted;

  /// No description provided for @addressSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ العنوان.'**
  String get addressSaved;

  /// No description provided for @addressDefaultSet.
  ///
  /// In ar, this message translates to:
  /// **'أصبح هذا عنوانك الافتراضي.'**
  String get addressDefaultSet;

  /// No description provided for @addressNewTitle.
  ///
  /// In ar, this message translates to:
  /// **'عنوان جديد'**
  String get addressNewTitle;

  /// No description provided for @addressEditTitle.
  ///
  /// In ar, this message translates to:
  /// **'تعديل العنوان'**
  String get addressEditTitle;

  /// No description provided for @addressRecipient.
  ///
  /// In ar, this message translates to:
  /// **'اسم المستلم'**
  String get addressRecipient;

  /// No description provided for @addressPhoneInvalid.
  ///
  /// In ar, this message translates to:
  /// **'رقم غير صحيح'**
  String get addressPhoneInvalid;

  /// No description provided for @addressSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ العنوان'**
  String get addressSave;

  /// No description provided for @addressSaveChanges.
  ///
  /// In ar, this message translates to:
  /// **'حفظ التعديلات'**
  String get addressSaveChanges;

  /// No description provided for @pointsAmount.
  ///
  /// In ar, this message translates to:
  /// **'{points} نقطة'**
  String pointsAmount(String points);

  /// No description provided for @rewardsAvailable.
  ///
  /// In ar, this message translates to:
  /// **'متاحة'**
  String get rewardsAvailable;

  /// No description provided for @rewardsUsed.
  ///
  /// In ar, this message translates to:
  /// **'مستخدمة'**
  String get rewardsUsed;

  /// No description provided for @rewardsExpired.
  ///
  /// In ar, this message translates to:
  /// **'منتهية'**
  String get rewardsExpired;

  /// No description provided for @rewardsLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل المكافآت'**
  String get rewardsLoadFailed;

  /// No description provided for @rewardsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مكافآت هنا'**
  String get rewardsEmpty;

  /// No description provided for @rewardsEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'جوائز عجلة الحظ والكوبونات الخاصة بك تظهر هنا.'**
  String get rewardsEmptyBody;

  /// No description provided for @rewardsNote.
  ///
  /// In ar, this message translates to:
  /// **'كود واحد لكل طلب. إذا ألغيت الطلب ترجع المكافأة إلى هنا تلقائياً.'**
  String get rewardsNote;

  /// No description provided for @rewardsTryWheel.
  ///
  /// In ar, this message translates to:
  /// **'جرّب حظك في العجلة'**
  String get rewardsTryWheel;

  /// No description provided for @rewardDiscount.
  ///
  /// In ar, this message translates to:
  /// **'خصم {amount} على طلبك'**
  String rewardDiscount(String amount);

  /// No description provided for @rewardProduct.
  ///
  /// In ar, this message translates to:
  /// **'منتج هدية: {name}'**
  String rewardProduct(String name);

  /// No description provided for @rewardProductFallback.
  ///
  /// In ar, this message translates to:
  /// **'منتج'**
  String get rewardProductFallback;

  /// No description provided for @rewardFromWheel.
  ///
  /// In ar, this message translates to:
  /// **'من عجلة الحظ'**
  String get rewardFromWheel;

  /// No description provided for @rewardFromMaqam.
  ///
  /// In ar, this message translates to:
  /// **'من MAQAM'**
  String get rewardFromMaqam;

  /// No description provided for @rewardShipsFree.
  ///
  /// In ar, this message translates to:
  /// **'يُشحن مجاناً مع أي طلب'**
  String get rewardShipsFree;

  /// No description provided for @rewardNoExpiry.
  ///
  /// In ar, this message translates to:
  /// **'بدون تاريخ انتهاء'**
  String get rewardNoExpiry;

  /// No description provided for @rewardExpiresIn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي خلال {days} يوماً'**
  String rewardExpiresIn(int days);

  /// No description provided for @rewardUsedOn.
  ///
  /// In ar, this message translates to:
  /// **'استُخدم {date}'**
  String rewardUsedOn(String date);

  /// No description provided for @rewardExpiredOn.
  ///
  /// In ar, this message translates to:
  /// **'انتهى {date}'**
  String rewardExpiredOn(String date);

  /// No description provided for @rewardUseInCart.
  ///
  /// In ar, this message translates to:
  /// **'استخدم في السلة'**
  String get rewardUseInCart;

  /// No description provided for @wheelLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل العجلة'**
  String get wheelLoadFailed;

  /// No description provided for @wheelDisabled.
  ///
  /// In ar, this message translates to:
  /// **'عجلة الحظ متوقفة حالياً. جرّب لاحقاً.'**
  String get wheelDisabled;

  /// No description provided for @wheelSemantics.
  ///
  /// In ar, this message translates to:
  /// **'عجلة الحظ: {slices}'**
  String wheelSemantics(String slices);

  /// No description provided for @wheelCost.
  ///
  /// In ar, this message translates to:
  /// **'تكلفة اللفة'**
  String get wheelCost;

  /// No description provided for @wheelBalanceAfter.
  ///
  /// In ar, this message translates to:
  /// **'رصيدك بعد اللفة'**
  String get wheelBalanceAfter;

  /// No description provided for @wheelSpin.
  ///
  /// In ar, this message translates to:
  /// **'لفّ العجلة'**
  String get wheelSpin;

  /// No description provided for @wheelSpinning.
  ///
  /// In ar, this message translates to:
  /// **'العجلة تدور…'**
  String get wheelSpinning;

  /// No description provided for @wheelNotEnough.
  ///
  /// In ar, this message translates to:
  /// **'تحتاج {points} نقطة على الأقل للّفة.'**
  String wheelNotEnough(String points);

  /// No description provided for @wheelFooterPrefix.
  ///
  /// In ar, this message translates to:
  /// **'الجوائز تُضاف إلى '**
  String get wheelFooterPrefix;

  /// No description provided for @wheelFooterSuffix.
  ///
  /// In ar, this message translates to:
  /// **' وتُستخدم بكودها في السلة.'**
  String get wheelFooterSuffix;

  /// No description provided for @wheelWonTitle.
  ///
  /// In ar, this message translates to:
  /// **'مبروك!'**
  String get wheelWonTitle;

  /// No description provided for @wheelWonBody.
  ///
  /// In ar, this message translates to:
  /// **'ربحت {prize}.'**
  String wheelWonBody(String prize);

  /// No description provided for @wheelLostTitle.
  ///
  /// In ar, this message translates to:
  /// **'حظ أوفر'**
  String get wheelLostTitle;

  /// No description provided for @wheelLostBody.
  ///
  /// In ar, this message translates to:
  /// **'لم تربح هذه المرة. جرّب مرة أخرى.'**
  String get wheelLostBody;

  /// No description provided for @wheelOk.
  ///
  /// In ar, this message translates to:
  /// **'حسناً'**
  String get wheelOk;

  /// No description provided for @merchantDashboardTitle.
  ///
  /// In ar, this message translates to:
  /// **'لوحة التاجر'**
  String get merchantDashboardTitle;

  /// No description provided for @merchantLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل بيانات التاجر'**
  String get merchantLoadFailed;

  /// No description provided for @merchantBenefitCode.
  ///
  /// In ar, this message translates to:
  /// **'تحصل على كود تاجر خاص بك'**
  String get merchantBenefitCode;

  /// No description provided for @merchantBenefitPoints.
  ///
  /// In ar, this message translates to:
  /// **'تكسب نقاطاً كل مرة يمسح عميل كوداً ويختارك'**
  String get merchantBenefitPoints;

  /// No description provided for @merchantBenefitReview.
  ///
  /// In ar, this message translates to:
  /// **'يراجع فريق MAQAM طلبك ويصلك إشعار بالنتيجة'**
  String get merchantBenefitReview;

  /// No description provided for @merchantNameLabel.
  ///
  /// In ar, this message translates to:
  /// **'اسم النشاط التجاري'**
  String get merchantNameLabel;

  /// No description provided for @merchantNameHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: معرض النور'**
  String get merchantNameHint;

  /// No description provided for @merchantAddressLabel.
  ///
  /// In ar, this message translates to:
  /// **'عنوان المحل'**
  String get merchantAddressLabel;

  /// No description provided for @merchantAddressHint.
  ///
  /// In ar, this message translates to:
  /// **'المحافظة، المدينة، الشارع'**
  String get merchantAddressHint;

  /// No description provided for @merchantLogoLabel.
  ///
  /// In ar, this message translates to:
  /// **'شعار المحل'**
  String get merchantLogoLabel;

  /// No description provided for @merchantLogoPick.
  ///
  /// In ar, this message translates to:
  /// **'رفع صورة'**
  String get merchantLogoPick;

  /// No description provided for @merchantLogoChosen.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيار الصورة'**
  String get merchantLogoChosen;

  /// No description provided for @merchantLogoHint.
  ///
  /// In ar, this message translates to:
  /// **'JPG أو PNG حتى 2MB'**
  String get merchantLogoHint;

  /// No description provided for @merchantSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get merchantSubmit;

  /// No description provided for @merchantSubmitNote.
  ///
  /// In ar, this message translates to:
  /// **'الكود لا يعمل في المسح قبل موافقة الإدارة.'**
  String get merchantSubmitNote;

  /// No description provided for @merchantApplied.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك. سيصلك إشعار بعد المراجعة.'**
  String get merchantApplied;

  /// No description provided for @merchantApproved.
  ///
  /// In ar, this message translates to:
  /// **'تاجر معتمد'**
  String get merchantApproved;

  /// No description provided for @merchantPending.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get merchantPending;

  /// No description provided for @merchantCodeLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود التاجر'**
  String get merchantCodeLabel;

  /// No description provided for @merchantShare.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة'**
  String get merchantShare;

  /// No description provided for @merchantShareText.
  ///
  /// In ar, this message translates to:
  /// **'اشتريت من {name}؟ أدخل كود التاجر {code} عند مسح عبوة MAQAM.'**
  String merchantShareText(String name, String code);

  /// No description provided for @merchantCodeCopied.
  ///
  /// In ar, this message translates to:
  /// **'تم نسخ كود التاجر.'**
  String get merchantCodeCopied;

  /// No description provided for @merchantCodeHint.
  ///
  /// In ar, this message translates to:
  /// **'اطلب من العميل إدخال هذا الكود عند مسح أي عبوة اشتراها منك.'**
  String get merchantCodeHint;

  /// No description provided for @merchantPendingHint.
  ///
  /// In ar, this message translates to:
  /// **'طلبك قيد المراجعة. سيعمل الكود بعد موافقة الإدارة ويصلك إشعار بذلك.'**
  String get merchantPendingHint;

  /// No description provided for @merchantLogoPlaceholder.
  ///
  /// In ar, this message translates to:
  /// **'الشعار'**
  String get merchantLogoPlaceholder;

  /// No description provided for @merchantChangeLogo.
  ///
  /// In ar, this message translates to:
  /// **'تغيير شعار المحل'**
  String get merchantChangeLogo;

  /// No description provided for @merchantStatPoints.
  ///
  /// In ar, this message translates to:
  /// **'إجمالي نقاطك'**
  String get merchantStatPoints;

  /// No description provided for @merchantStatScans.
  ///
  /// In ar, this message translates to:
  /// **'عدد المسحات'**
  String get merchantStatScans;

  /// No description provided for @merchantStatMonth.
  ///
  /// In ar, this message translates to:
  /// **'مسحات هذا الشهر'**
  String get merchantStatMonth;

  /// No description provided for @merchantStatCustomers.
  ///
  /// In ar, this message translates to:
  /// **'عملاء مختلفون'**
  String get merchantStatCustomers;

  /// No description provided for @merchantPointsNote.
  ///
  /// In ar, this message translates to:
  /// **'نقاط التاجر رصيد تراكمي للعرض حالياً، ولا يمكن استخدام كودك في مسحك أنت.'**
  String get merchantPointsNote;

  /// No description provided for @scanOfflineBanner.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتصال: المسح يُحفظ على هاتفك الآن'**
  String get scanOfflineBanner;

  /// No description provided for @scanOfflineSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ المسح'**
  String get scanOfflineSaved;

  /// No description provided for @scanOfflineExplain.
  ///
  /// In ar, this message translates to:
  /// **'نقاطك لم تُضف بعد. سنرفع المسحات تلقائياً ونضيف النقاط أول ما يرجع الإنترنت، حتى لو كان التطبيق مغلقاً.'**
  String get scanOfflineExplain;

  /// No description provided for @scanCodeTail.
  ///
  /// In ar, this message translates to:
  /// **'الكود {tail} ••••'**
  String scanCodeTail(String tail);

  /// No description provided for @scanPendingBadge.
  ///
  /// In ar, this message translates to:
  /// **'في الانتظار'**
  String get scanPendingBadge;

  /// No description provided for @scanPendingCount.
  ///
  /// In ar, this message translates to:
  /// **'في الانتظار ({count})'**
  String scanPendingCount(int count);

  /// No description provided for @pendingTitle.
  ///
  /// In ar, this message translates to:
  /// **'مسحات بدون إنترنت'**
  String get pendingTitle;

  /// No description provided for @pendingEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مسحات في الانتظار'**
  String get pendingEmpty;

  /// No description provided for @pendingEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'المسحات التي تتم بدون إنترنت تُحفظ هنا حتى تُرفع.'**
  String get pendingEmptyBody;

  /// No description provided for @pendingCount.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{مسحة واحدة في انتظار الرفع} =2{مسحتان في انتظار الرفع} few{{count} مسحات في انتظار الرفع} other{{count} مسحة في انتظار الرفع}}'**
  String pendingCount(int count);

  /// No description provided for @pendingAuto.
  ///
  /// In ar, this message translates to:
  /// **'تُرفع تلقائياً عند رجوع الاتصال'**
  String get pendingAuto;

  /// No description provided for @pendingUploadNow.
  ///
  /// In ar, this message translates to:
  /// **'رفع الآن'**
  String get pendingUploadNow;

  /// No description provided for @pendingStillOffline.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتصال بعد. سنحاول مرة أخرى تلقائياً.'**
  String get pendingStillOffline;

  /// No description provided for @pendingWaiting.
  ///
  /// In ar, this message translates to:
  /// **'في الانتظار'**
  String get pendingWaiting;

  /// No description provided for @pendingLastUpload.
  ///
  /// In ar, this message translates to:
  /// **'آخر عملية رفع'**
  String get pendingLastUpload;

  /// No description provided for @pendingRejected.
  ///
  /// In ar, this message translates to:
  /// **'لم يُقبل: {reason}'**
  String pendingRejected(String reason);

  /// No description provided for @pendingAlreadyCredited.
  ///
  /// In ar, this message translates to:
  /// **'أُضيف'**
  String get pendingAlreadyCredited;

  /// No description provided for @pendingHide.
  ///
  /// In ar, this message translates to:
  /// **'إخفاء'**
  String get pendingHide;

  /// No description provided for @pendingFooter.
  ///
  /// In ar, this message translates to:
  /// **'المسحات محفوظة على هذا الهاتف فقط. لا تحذف التطبيق قبل رفعها.'**
  String get pendingFooter;

  /// No description provided for @addressStreetDetail.
  ///
  /// In ar, this message translates to:
  /// **'الشارع، رقم العمارة، الدور'**
  String get addressStreetDetail;

  /// No description provided for @wheelLocked.
  ///
  /// In ar, this message translates to:
  /// **'عجلة الحظ مقفلة'**
  String get wheelLocked;

  /// No description provided for @seminarTitle.
  ///
  /// In ar, this message translates to:
  /// **'السيمينار'**
  String get seminarTitle;

  /// No description provided for @seminarLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل السيمينار'**
  String get seminarLoadFailed;

  /// No description provided for @seminarLive.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ الآن'**
  String get seminarLive;

  /// No description provided for @seminarEnded.
  ///
  /// In ar, this message translates to:
  /// **'انتهى'**
  String get seminarEnded;

  /// No description provided for @seminarEndsIn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي خلال {time}'**
  String seminarEndsIn(String time);

  /// No description provided for @seminarDays.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{يوم} =2{يومين} few{{count} أيام} other{{count} يوماً}}'**
  String seminarDays(int count);

  /// No description provided for @seminarHours.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{ساعة} =2{ساعتين} few{{count} ساعات} other{{count} ساعة}}'**
  String seminarHours(int count);

  /// No description provided for @seminarMinutes.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{دقيقة} =2{دقيقتين} few{{count} دقائق} other{{count} دقيقة}}'**
  String seminarMinutes(int count);

  /// No description provided for @seminarPromoBody.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{سحب على جائزة} =2{سحب على جائزتين} few{سحب على {count} جوائز} other{سحب على {count} جائزة}}'**
  String seminarPromoBody(int count);

  /// No description provided for @seminarRegisteredCount.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =0{كن أول المسجلين} =1{مسجل واحد} =2{مسجلان} few{{count} مسجلين} other{{count} مسجل}}'**
  String seminarRegisteredCount(int count);

  /// No description provided for @seminarJoin.
  ///
  /// In ar, this message translates to:
  /// **'سجّل في السحب'**
  String get seminarJoin;

  /// No description provided for @seminarYouAreIn.
  ///
  /// In ar, this message translates to:
  /// **'أنت مسجّل'**
  String get seminarYouAreIn;

  /// No description provided for @seminarRegisteredDone.
  ///
  /// In ar, this message translates to:
  /// **'تم تسجيلك في السحب. بالتوفيق!'**
  String get seminarRegisteredDone;

  /// No description provided for @seminarRegisteredLive.
  ///
  /// In ar, this message translates to:
  /// **'أنت مسجّل في السحب. تابع النتائج هنا.'**
  String get seminarRegisteredLive;

  /// No description provided for @seminarRegisteredEnded.
  ///
  /// In ar, this message translates to:
  /// **'كنت مسجّلاً في هذا السحب.'**
  String get seminarRegisteredEnded;

  /// No description provided for @seminarClosed.
  ///
  /// In ar, this message translates to:
  /// **'انتهى التسجيل في هذا السيمينار.'**
  String get seminarClosed;

  /// No description provided for @seminarHowItWorks.
  ///
  /// In ar, this message translates to:
  /// **'سجّل أثناء السيمينار، ويُسحب الفائزون عشوائياً. كل شخص يفوز بجائزة واحدة على الأكثر.'**
  String get seminarHowItWorks;

  /// No description provided for @seminarPrizes.
  ///
  /// In ar, this message translates to:
  /// **'الجوائز والفائزون'**
  String get seminarPrizes;

  /// No description provided for @seminarNotDrawn.
  ///
  /// In ar, this message translates to:
  /// **'لم تُسحب بعد'**
  String get seminarNotDrawn;

  /// No description provided for @seminarWinner.
  ///
  /// In ar, this message translates to:
  /// **'الفائز: {name}'**
  String seminarWinner(String name);

  /// No description provided for @seminarWinnerYou.
  ///
  /// In ar, this message translates to:
  /// **'الفائز: أنت!'**
  String get seminarWinnerYou;

  /// No description provided for @seminarYouWon.
  ///
  /// In ar, this message translates to:
  /// **'مبروك! فزت بـ {prize}'**
  String seminarYouWon(String prize);

  /// No description provided for @seminarCollect.
  ///
  /// In ar, this message translates to:
  /// **'تواصل مع فريق MAQAM لاستلام جائزتك.'**
  String get seminarCollect;

  /// No description provided for @lockWithFaceId.
  ///
  /// In ar, this message translates to:
  /// **'بـ Face ID'**
  String get lockWithFaceId;

  /// No description provided for @lockWithFace.
  ///
  /// In ar, this message translates to:
  /// **'ببصمة الوجه'**
  String get lockWithFace;

  /// No description provided for @lockWithFingerprint.
  ///
  /// In ar, this message translates to:
  /// **'ببصمة الإصبع'**
  String get lockWithFingerprint;

  /// No description provided for @biometricSetupBodyWith.
  ///
  /// In ar, this message translates to:
  /// **'ادخل {method} بدل كتابة رقمك وكلمة المرور، حتى بعد تسجيل الخروج.'**
  String biometricSetupBodyWith(String method);

  /// No description provided for @biometricSetupEnableWith.
  ///
  /// In ar, this message translates to:
  /// **'تفعيل الدخول {method}'**
  String biometricSetupEnableWith(String method);

  /// No description provided for @unlockButtonWith.
  ///
  /// In ar, this message translates to:
  /// **'الدخول {method}'**
  String unlockButtonWith(String method);

  /// No description provided for @unlockHintFace.
  ///
  /// In ar, this message translates to:
  /// **'انظر إلى الهاتف للدخول'**
  String get unlockHintFace;

  /// No description provided for @unlockHintFingerprint.
  ///
  /// In ar, this message translates to:
  /// **'المس مستشعر البصمة للدخول'**
  String get unlockHintFingerprint;

  /// No description provided for @unlockBrowseAsGuest.
  ///
  /// In ar, this message translates to:
  /// **'تصفّح كزائر'**
  String get unlockBrowseAsGuest;

  /// No description provided for @merchantRecentScans.
  ///
  /// In ar, this message translates to:
  /// **'آخر من استخدم كودك'**
  String get merchantRecentScans;

  /// No description provided for @merchantViewAll.
  ///
  /// In ar, this message translates to:
  /// **'عرض الكل'**
  String get merchantViewAll;

  /// No description provided for @merchantNoScans.
  ///
  /// In ar, this message translates to:
  /// **'لم يستخدم أحد كودك بعد. شاركه مع عملائك ليبدأ رصيدك.'**
  String get merchantNoScans;

  /// No description provided for @merchantScansTitle.
  ///
  /// In ar, this message translates to:
  /// **'المسحات بكودك'**
  String get merchantScansTitle;

  /// No description provided for @merchantScansLoadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل المسحات'**
  String get merchantScansLoadFailed;

  /// No description provided for @merchantScanPoints.
  ///
  /// In ar, this message translates to:
  /// **'+{points} نقطة'**
  String merchantScanPoints(int points);

  /// No description provided for @merchantStatMonthPoints.
  ///
  /// In ar, this message translates to:
  /// **'نقاط هذا الشهر'**
  String get merchantStatMonthPoints;

  /// No description provided for @merchantStatTotalScans.
  ///
  /// In ar, this message translates to:
  /// **'إجمالي المسحات'**
  String get merchantStatTotalScans;

  /// No description provided for @merchantStatTotalPoints.
  ///
  /// In ar, this message translates to:
  /// **'إجمالي النقاط'**
  String get merchantStatTotalPoints;

  /// No description provided for @merchantDashboard.
  ///
  /// In ar, this message translates to:
  /// **'لوحة تحكم التاجر'**
  String get merchantDashboard;

  /// No description provided for @merchantTechnicians.
  ///
  /// In ar, this message translates to:
  /// **'الفنيين'**
  String get merchantTechnicians;

  /// No description provided for @merchantTechnicianScans.
  ///
  /// In ar, this message translates to:
  /// **'السكانات'**
  String get merchantTechnicianScans;

  /// No description provided for @merchantTechnicianPoints.
  ///
  /// In ar, this message translates to:
  /// **'النقاط'**
  String get merchantTechnicianPoints;

  /// No description provided for @merchantTechnicianBalance.
  ///
  /// In ar, this message translates to:
  /// **'الرصيد'**
  String get merchantTechnicianBalance;

  /// No description provided for @merchantDeductPoints.
  ///
  /// In ar, this message translates to:
  /// **'خصم نقاط'**
  String get merchantDeductPoints;

  /// No description provided for @merchantDeductAmount.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ'**
  String get merchantDeductAmount;

  /// No description provided for @merchantDeductAmountHint.
  ///
  /// In ar, this message translates to:
  /// **'عدد النقاط المراد خصمها'**
  String get merchantDeductAmountHint;

  /// No description provided for @merchantDeductReason.
  ///
  /// In ar, this message translates to:
  /// **'السبب'**
  String get merchantDeductReason;

  /// No description provided for @merchantDeductReasonHint.
  ///
  /// In ar, this message translates to:
  /// **'مثال: مرتجع منتج'**
  String get merchantDeductReasonHint;

  /// No description provided for @merchantDeductSubmit.
  ///
  /// In ar, this message translates to:
  /// **'خصم'**
  String get merchantDeductSubmit;

  /// No description provided for @merchantDeductSuccess.
  ///
  /// In ar, this message translates to:
  /// **'تم خصم النقاط بنجاح.'**
  String get merchantDeductSuccess;

  /// No description provided for @merchantDeductAmountRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل المبلغ.'**
  String get merchantDeductAmountRequired;

  /// No description provided for @merchantDeductReasonRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل السبب.'**
  String get merchantDeductReasonRequired;

  /// No description provided for @merchantTransactions.
  ///
  /// In ar, this message translates to:
  /// **'العمليات'**
  String get merchantTransactions;

  /// No description provided for @merchantTransactionEarned.
  ///
  /// In ar, this message translates to:
  /// **'إضافة'**
  String get merchantTransactionEarned;

  /// No description provided for @merchantTransactionDeducted.
  ///
  /// In ar, this message translates to:
  /// **'خصم'**
  String get merchantTransactionDeducted;

  /// No description provided for @merchantNoTechnicians.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد فنيين مربوطين بتاجرك بعد.'**
  String get merchantNoTechnicians;

  /// No description provided for @merchantNoTransactions.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عمليات بعد.'**
  String get merchantNoTransactions;

  /// No description provided for @merchantLoadMore.
  ///
  /// In ar, this message translates to:
  /// **'تحميل المزيد'**
  String get merchantLoadMore;

  /// No description provided for @merchantUnknownCustomer.
  ///
  /// In ar, this message translates to:
  /// **'عميل'**
  String get merchantUnknownCustomer;

  /// No description provided for @merchantSince.
  ///
  /// In ar, this message translates to:
  /// **'تاجر منذ {date}'**
  String merchantSince(String date);

  /// No description provided for @merchantLastScan.
  ///
  /// In ar, this message translates to:
  /// **'آخر مسح: {time}'**
  String merchantLastScan(String time);

  /// No description provided for @accountPointsBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيد النقاط'**
  String get accountPointsBalance;

  /// No description provided for @accountRank.
  ///
  /// In ar, this message translates to:
  /// **'المستوى'**
  String get accountRank;

  /// No description provided for @accountToNextRank.
  ///
  /// In ar, this message translates to:
  /// **'{points} نقطة للوصول إلى {rank}'**
  String accountToNextRank(String points, String rank);

  /// No description provided for @accountTopRank.
  ///
  /// In ar, this message translates to:
  /// **'وصلت لأعلى مستوى'**
  String get accountTopRank;

  /// No description provided for @accountMerchantOpen.
  ///
  /// In ar, this message translates to:
  /// **'لوحة التاجر'**
  String get accountMerchantOpen;

  /// No description provided for @accountMerchantPendingTitle.
  ///
  /// In ar, this message translates to:
  /// **'طلب التاجر قيد المراجعة'**
  String get accountMerchantPendingTitle;

  /// No description provided for @accountCopyCode.
  ///
  /// In ar, this message translates to:
  /// **'نسخ الكود'**
  String get accountCopyCode;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
