// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'MAQAM';

  @override
  String get onboardingStart => 'Get started';

  @override
  String get onboardingGuest => 'Browse the store as a guest';

  @override
  String get loginTitle => 'Sign in';

  @override
  String get loginSubtitle =>
      'Enter your mobile number. No password: you confirm it with a short WhatsApp message.';

  @override
  String get loginPhoneLabel => 'Mobile number';

  @override
  String get loginContinue => 'Continue';

  @override
  String get loginNoSpam =>
      'We will never message you before you message us first.';

  @override
  String get loginPhoneRequired => 'Enter your mobile number.';

  @override
  String get loginPhoneInvalid =>
      'This mobile number is not valid. Example: 10 1234 5678';

  @override
  String get loginLegalPrefix => 'By continuing you agree to the ';

  @override
  String get loginLegalTerms => 'Terms and Conditions';

  @override
  String get loginLegalAnd => ' and ';

  @override
  String get loginLegalPrivacy => 'Privacy Policy';

  @override
  String get switchLanguage => 'العربية';

  @override
  String get back => 'Back';

  @override
  String get catalogAll => 'All';

  @override
  String get catalogLoadFailed => 'Could not load products';

  @override
  String get catalogEmpty => 'No products found';

  @override
  String get retry => 'Try again';

  @override
  String get priceOnRequest => 'Price on request';

  @override
  String priceEgp(String amount) {
    return 'EGP $amount';
  }

  @override
  String stepOf(int step, int total) {
    return 'Step $step of $total';
  }

  @override
  String get sendCodeTitle => 'Send the code on WhatsApp';

  @override
  String get sendCodeSubtitlePrefix => 'Send it from ';

  @override
  String get sendCodeSubtitleSuffix =>
      ' and we will reply with your sign-in code.';

  @override
  String get sendCodeLabel => 'Confirmation code';

  @override
  String get sendCodeCopy => 'Copy code';

  @override
  String get sendCodeCopied => 'Code copied';

  @override
  String get sendCodeOpenWhatsapp => 'Open WhatsApp and send the code';

  @override
  String get sendCodeWhatsappMissing =>
      'Could not open WhatsApp. Copy the code and send it yourself.';

  @override
  String get sendCodeWaiting => 'Waiting for your message...';

  @override
  String get sendCodeStep1 => 'Tap the button: the message is already written';

  @override
  String get sendCodeStep2 => 'Tap Send inside WhatsApp';

  @override
  String get sendCodeStep3 =>
      'Come back and enter the sign-in code you receive';

  @override
  String sendCodeValidFor(int minutes) {
    return 'The code is valid for $minutes minutes · ';
  }

  @override
  String get sendCodeChangeNumber => 'Change number';

  @override
  String get sendCodeExpired => 'This code has expired.';

  @override
  String get startOver => 'Start over';

  @override
  String get otpTitle => 'Enter the sign-in code';

  @override
  String get otpSubtitle =>
      'We got your message and sent the code to you on WhatsApp.';

  @override
  String get otpFieldLabel => '6-digit sign-in code';

  @override
  String get otpExpiresIn => 'The code expires in ';

  @override
  String get otpExpired => 'The sign-in code has expired.';

  @override
  String get otpSubmit => 'Sign in';

  @override
  String get otpNotReceivedPrefix =>
      'Didn\'t get the code? Make sure you sent from the same number, then ';

  @override
  String get otpDoNotShare =>
      'Never share this code with anyone. The MAQAM team will never ask you for it.';

  @override
  String get errorNetwork =>
      'Could not connect. Check your internet and try again.';

  @override
  String get errorGeneric => 'Something went wrong. Please try again.';

  @override
  String get signIn => 'Sign in';

  @override
  String get signOut => 'Sign out';

  @override
  String get skip => 'Skip';

  @override
  String get biometricSetupTitle => 'Sign in faster next time';

  @override
  String get biometricSetupPoint1 =>
      'Your biometrics stay on your device and are never sent to us';

  @override
  String get biometricSetupPoint2 =>
      'You can turn this off at any time from My Account';

  @override
  String get biometricSetupPoint3 =>
      'Signing in with a WhatsApp code is always available';

  @override
  String get biometricSetupNotNow => 'Not now';

  @override
  String get biometricSetupFailed =>
      'We could not verify you. Try again or choose Not now.';

  @override
  String get biometricEnableReason =>
      'Confirm it\'s you to enable fingerprint or face sign-in';

  @override
  String get unlockWelcomeBack => 'Welcome back';

  @override
  String unlockWelcomeBackName(String name) {
    return 'Welcome back, $name';
  }

  @override
  String get unlockFailed => 'We could not verify you. Please try again.';

  @override
  String get unlockReason => 'Unlock MAQAM';

  @override
  String get unlockUseWhatsapp => 'Sign in with a WhatsApp code';

  @override
  String get unlockDifferentAccount => 'Not you? Sign in with another number';

  @override
  String get navHome => 'Home';

  @override
  String get navStore => 'Store';

  @override
  String get navScan => 'Scan code';

  @override
  String get navWallet => 'Wallet';

  @override
  String get navAccount => 'Account';

  @override
  String homeGreeting(String name) {
    return 'Hello $name · your points';
  }

  @override
  String get homeGreetingNoName => 'Hello · your points';

  @override
  String get pointsUnit => 'points';

  @override
  String homeToNextRank(int points, String rank) {
    return '$points points to reach $rank';
  }

  @override
  String get homeTopRank => 'You are at the top rank';

  @override
  String get rankUpCongrats => 'Congratulations!';

  @override
  String rankUpMessage(String rank) {
    return 'You have been promoted to $rank';
  }

  @override
  String get rankUpDismiss => 'Got it';

  @override
  String get homeGuestTitle => 'Earn points with every pack';

  @override
  String get homeGuestBody =>
      'Sign in to scan codes and collect points and prizes.';

  @override
  String get actionScan => 'Scan code';

  @override
  String get actionWheel => 'Lucky wheel';

  @override
  String get actionRewards => 'My rewards';

  @override
  String get actionOrders => 'My orders';

  @override
  String get actionLeaderboard => 'Leaderboard';

  @override
  String get leaderboardTitle => 'Leaderboard';

  @override
  String get leaderboardEmpty => 'No leaders yet';

  @override
  String leaderboardPoints(String points) {
    return '$points pts';
  }

  @override
  String get homeNewArrivals => 'New arrivals';

  @override
  String get viewAll => 'View all';

  @override
  String get chat => 'Assistant';

  @override
  String get notifications => 'Notifications';

  @override
  String get storeSearchHint => 'Search by product name or code';

  @override
  String storeCount(int count) {
    return '$count products';
  }

  @override
  String get cart => 'Cart';

  @override
  String get sortNewest => 'Newest';

  @override
  String get sortPriceLow => 'Price: low to high';

  @override
  String get sortPriceHigh => 'Price: high to low';

  @override
  String get sortBy => 'Sort';

  @override
  String get outOfStock => 'Out of stock';

  @override
  String get walletAvailable => 'Available to spend';

  @override
  String get walletTotalEarned => 'Total earned';

  @override
  String walletNextRankAt(String rank, int points) {
    return '$rank at $points';
  }

  @override
  String get walletRankNote =>
      'Your rank follows total points earned: spending never lowers it.';

  @override
  String get walletEarn => 'Earn points';

  @override
  String get walletRecent => 'Recent activity';

  @override
  String get filterEarned => 'Earned';

  @override
  String get filterSpent => 'Spent';

  @override
  String get walletNoTransactions => 'No activity yet';

  @override
  String get walletLoadFailed => 'Could not load your wallet';

  @override
  String get walletGuest => 'Sign in to see your points and activity.';

  @override
  String get today => 'Today';

  @override
  String get yesterday => 'Yesterday';

  @override
  String get accountEdit => 'Edit';

  @override
  String get accountMerchantTitle => 'Become an approved merchant';

  @override
  String get accountMerchantBody =>
      'Earn points on every scan made with your code';

  @override
  String get accountAddresses => 'My addresses';

  @override
  String get accountPendingScans => 'Scans waiting to upload';

  @override
  String get accountBiometricsOff => 'Off';

  @override
  String get accountChangePhone => 'Change mobile number';

  @override
  String get accountLanguage => 'Language';

  @override
  String get languageName => 'English';

  @override
  String get accountHelp => 'Assistant and FAQ';

  @override
  String get accountLegal => 'Terms and privacy policy';

  @override
  String get accountDelete => 'Delete account';

  @override
  String accountVersion(String version) {
    return 'Version $version';
  }

  @override
  String get accountGuest => 'Sign in to manage your account and orders.';

  @override
  String get accountNoName => 'MAQAM customer';

  @override
  String get signOutConfirmTitle => 'Sign out?';

  @override
  String get signOutConfirmBody =>
      'You will need a WhatsApp code to sign in again.';

  @override
  String get cancel => 'Cancel';

  @override
  String get onboardingNext => 'Next';

  @override
  String onboardingPage(int current, int total) {
    return 'Screen $current of $total';
  }

  @override
  String get onboarding1Title => 'Prestige in Every Touch';

  @override
  String get onboarding1Body =>
      'Shop MAQAM switches, sockets and lighting, delivered to your door.';

  @override
  String get onboarding1ChipA => 'Store';

  @override
  String get onboarding1ChipB => 'Home delivery';

  @override
  String get onboarding2Title => 'Scan and earn';

  @override
  String get onboarding2Body =>
      'Scan the code on every pack to add points to your balance right away.';

  @override
  String get onboarding2ChipA => 'Pack code';

  @override
  String get onboarding2ChipB => 'Instant points';

  @override
  String get onboarding3Title => 'Your rank grows with your points';

  @override
  String get onboarding3Body =>
      'Every point you earn brings a higher rank and more benefits, and spending never lowers it.';

  @override
  String get onboarding3ChipA => 'Higher rank';

  @override
  String get onboarding3ChipB => 'More benefits';

  @override
  String get onboarding4Title => 'Lucky wheel and rewards';

  @override
  String get onboarding4Body =>
      'Trade points for a spin of the lucky wheel and win discounts and gifts kept in your rewards.';

  @override
  String get onboarding4ChipA => 'Discounts';

  @override
  String get onboarding4ChipB => 'Gifts';

  @override
  String get onboarding5Title => 'Secure sign-in, no password';

  @override
  String get onboarding5Body =>
      'Sign in with your mobile number, confirm it on WhatsApp, then unlock with your fingerprint or face.';

  @override
  String get onboarding5ChipA => 'WhatsApp';

  @override
  String get onboarding5ChipB => 'Fingerprint or face';

  @override
  String get passwordTitle => 'Enter your password';

  @override
  String get passwordSubtitlePrefix => 'To sign in to the account for ';

  @override
  String get passwordLabel => 'Password';

  @override
  String get passwordShow => 'Show password';

  @override
  String get passwordHide => 'Hide password';

  @override
  String get passwordRequired => 'Enter your password.';

  @override
  String get passwordSubmit => 'Sign in';

  @override
  String get passwordForgot =>
      'Forgot your password? Sign in with a WhatsApp code';

  @override
  String get setupTitle => 'Complete your details';

  @override
  String get setupSubtitle =>
      'Choose a name and a password to sign in next time without waiting for a WhatsApp code.';

  @override
  String get setupNameLabel => 'Full name';

  @override
  String get setupNameHint => 'Example: Ahmed Mahmoud';

  @override
  String get setupNameRequired => 'Enter your name.';

  @override
  String get setupPasswordLabel => 'Password';

  @override
  String get setupPasswordHint => 'At least 8 letters or digits';

  @override
  String get setupPasswordTooShort =>
      'The password must be at least 8 characters.';

  @override
  String get setupConfirmLabel => 'Confirm password';

  @override
  String get setupConfirmMismatch => 'The two passwords do not match.';

  @override
  String get setupGovernorateLabel => 'Governorate';

  @override
  String get setupGovernorateHint => 'Select your governorate';

  @override
  String get setupAreaLabel => 'Area';

  @override
  String get setupAreaHint => 'Your area or district';

  @override
  String get bindMerchantTitle => 'Enter your merchant code';

  @override
  String get bindMerchantSubtitle =>
      'Enter the code of the merchant you work with. You can change it later from your profile.';

  @override
  String get bindMerchantCodeLabel => 'Merchant code';

  @override
  String get bindMerchantCodeHint => 'Enter the merchant code';

  @override
  String get bindMerchantSubmit => 'Confirm';

  @override
  String get bindMerchantCodeRequired => 'Enter the merchant code.';

  @override
  String get merchantChangeTitle => 'Change merchant';

  @override
  String get merchantChangeSubtitle =>
      'Enter the code of the new merchant. Your request will be reviewed by the admin.';

  @override
  String get merchantChangeSubmit => 'Send request';

  @override
  String get merchantChangeCodeRequired => 'Enter the new merchant code.';

  @override
  String get merchantChangePending =>
      'Your merchant change request is pending review.';

  @override
  String get merchantChangeApproved =>
      'Your merchant change request was approved.';

  @override
  String get merchantChangeRejected =>
      'Your merchant change request was rejected.';

  @override
  String get merchantChangeRequestSent =>
      'Your request has been sent and is pending review.';

  @override
  String get setupSave => 'Save and continue';

  @override
  String get setupLater => 'Later';

  @override
  String get setupNote =>
      'Signing in with a WhatsApp code is always available if you forget your password.';

  @override
  String get resetTitle => 'New password';

  @override
  String get resetSubtitle =>
      'Your number is verified. Choose a new password for your account.';

  @override
  String get productInStock => 'In stock';

  @override
  String get productColor => 'Colour: ';

  @override
  String get productDescription => 'Description';

  @override
  String get productQrNote =>
      'There is a QR code inside the pack: scan it after delivery to earn points.';

  @override
  String get productAddToCart => 'Add to cart';

  @override
  String get productAdded => 'Added to your cart.';

  @override
  String get productViewCart => 'View cart';

  @override
  String get productLoadFailed => 'Could not load this product';

  @override
  String get productSignInToBuy => 'Sign in to add products to your cart.';

  @override
  String get quantityIncrease => 'Increase quantity';

  @override
  String get quantityDecrease => 'Decrease quantity';

  @override
  String get cartTitle => 'Cart';

  @override
  String cartItemsCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count items',
      one: '1 item',
      zero: 'empty',
    );
    return '$_temp0';
  }

  @override
  String get cartEmpty => 'Your cart is empty';

  @override
  String get cartEmptyBody =>
      'Add products from the store and they will appear here.';

  @override
  String get cartBrowse => 'Browse the store';

  @override
  String get cartGuest => 'Sign in to see your cart and place your order.';

  @override
  String get cartLoadFailed => 'Could not load your cart';

  @override
  String get cartCouponLabel => 'Discount or reward code';

  @override
  String get cartCouponHint => 'Enter the code';

  @override
  String get cartCouponApply => 'Apply';

  @override
  String get cartCouponRemove => 'Remove';

  @override
  String get cartCouponApplied => 'Code applied.';

  @override
  String get summarySubtotal => 'Subtotal';

  @override
  String get summaryDiscount => 'Discount';

  @override
  String get summaryShipping => 'Shipping';

  @override
  String get summaryTotal => 'Total';

  @override
  String get cartCheckout => 'Continue to checkout';

  @override
  String get checkoutTitle => 'Checkout';

  @override
  String get checkoutAddress => 'Delivery address';

  @override
  String get checkoutChange => 'Change';

  @override
  String get checkoutUseSaved => 'Use my saved address';

  @override
  String get addressName => 'Recipient name';

  @override
  String get addressPhone => 'Mobile number';

  @override
  String get addressGovernorate => 'Governorate';

  @override
  String get addressCity => 'City';

  @override
  String get addressStreet => 'Full address';

  @override
  String get addressStreetHint => 'Street, building number, flat';

  @override
  String get fieldRequired => 'This field is required.';

  @override
  String get checkoutPayment => 'Payment method';

  @override
  String get paymentCod => 'Cash on delivery';

  @override
  String get paymentOnline => 'Card or mobile wallet';

  @override
  String get paymentOnlineBody => 'Visa, Mastercard, Meeza, Vodafone Cash';

  @override
  String get paymentPoints => 'Loyalty points';

  @override
  String paymentPointsBalance(int points) {
    return 'Your balance: $points points';
  }

  @override
  String get checkoutNotes => 'Notes for the courier';

  @override
  String get checkoutOptional => ' (optional)';

  @override
  String get checkoutNotesHint => 'Example: call before arriving';

  @override
  String get checkoutConfirm => 'Place order';

  @override
  String get checkoutPaymentOpenFailed =>
      'Your order was created, but the payment page could not be opened. You can pay later from your orders.';

  @override
  String get orderPlacedTitle => 'We got your order';

  @override
  String get orderPlacedBody =>
      'We are preparing it now and will notify you at every step.';

  @override
  String get orderPlacedPointsTitle => 'Don\'t forget your points';

  @override
  String get orderPlacedPointsBody =>
      'On delivery, scan the QR code inside every pack to earn loyalty points.';

  @override
  String get orderPlacedTrack => 'Track order';

  @override
  String get orderPlacedHome => 'Back to home';

  @override
  String get close => 'Close';

  @override
  String get scanTitle => 'Scan QR code';

  @override
  String get scanTorchOn => 'Turn the flash on';

  @override
  String get scanTorchOff => 'Turn the flash off';

  @override
  String get scanHint =>
      'Scratch off the cover inside the pack\nand point the camera at the code';

  @override
  String get scanCameraUnavailable =>
      'The camera could not be started. Allow the app to use it in your device settings, or type the code in.';

  @override
  String get scanMerchantQuestion => 'Which merchant did you buy from?';

  @override
  String get scanOptional => 'Optional';

  @override
  String get scanNoMerchant => 'No merchant';

  @override
  String get scanMerchantCodeHint => 'New merchant code, e.g. M-AB12CD34';

  @override
  String get scanMerchantAdd => 'Add';

  @override
  String get scanManualEntry => 'Type the code';

  @override
  String get scanManualHint => 'The code printed inside the pack';

  @override
  String get scanManualSubmit => 'Confirm';

  @override
  String get scanResultAdded => 'You earned';

  @override
  String get scanResultBalance => 'Your balance now';

  @override
  String get scanResultMerchant => 'Merchant';

  @override
  String scanResultToRank(String rank) {
    return 'Left to reach $rank';
  }

  @override
  String get scanAnother => 'Scan another code';

  @override
  String get scanOpenWallet => 'Open wallet';

  @override
  String get paymentTitle => 'Secure payment';

  @override
  String get paymentChecking => 'Checking your payment...';

  @override
  String get paymentLeaveTitle => 'Close the payment page?';

  @override
  String get paymentLeaveBody =>
      'Your order is saved. It will not be prepared until it is paid.';

  @override
  String get paymentLeaveStay => 'Keep paying';

  @override
  String get paymentLeaveConfirm => 'Close';

  @override
  String get orderPaidTitle => 'Paid, and we got your order';

  @override
  String get orderPaymentPendingBody =>
      'We have not confirmed the payment yet. We will start on your order as soon as it is.';

  @override
  String get orderPaymentFailedTitle => 'The payment did not go through';

  @override
  String get orderPaymentFailedBody =>
      'Nothing was charged. You can order again and choose another payment method.';

  @override
  String productLowStock(int count) {
    return 'Only $count left';
  }

  @override
  String get productPerkDelivery => 'Home delivery';

  @override
  String get productPerkCod => 'Cash on delivery';

  @override
  String get productPerkPoints => 'Points in every pack';

  @override
  String get productSpecs => 'Specifications';

  @override
  String get productSpecSku => 'Product code';

  @override
  String get productSpecCategory => 'Category';

  @override
  String get productSpecWeight => 'Weight';

  @override
  String get productSpecDimensions => 'Dimensions';

  @override
  String productWeightKg(String weight) {
    return '$weight kg';
  }

  @override
  String get productReadMore => 'Read more';

  @override
  String get productReadLess => 'Show less';

  @override
  String get productSimilar => 'Similar products';

  @override
  String productGallery(int current, int total) {
    return 'Image $current of $total';
  }

  @override
  String get productZoom => 'Zoom image';

  @override
  String productAddWithTotal(String total) {
    return 'Add to cart · $total';
  }

  @override
  String get lockPinOfferTitle => 'Protect your app with a PIN';

  @override
  String get lockPinOfferBody =>
      'Create a 4-digit code that is asked for when the app opens, so nobody else can open it.';

  @override
  String get lockPinPoint1 =>
      'The PIN stays on your device and is never sent to us';

  @override
  String get lockPinCreate => 'Create a PIN';

  @override
  String get lockUsePinInstead => 'Use a PIN instead';

  @override
  String get pinSetupTitle => 'Create a PIN';

  @override
  String get pinSetupBody => 'Choose 4 digits you will remember.';

  @override
  String get pinConfirmTitle => 'Enter it again';

  @override
  String get pinConfirmBody => 'To make sure you typed it correctly.';

  @override
  String get pinMismatch => 'The two PINs do not match. Start again.';

  @override
  String get pinUnlockHint => 'Enter your PIN';

  @override
  String pinWrong(int count) {
    return 'Wrong PIN. Attempts left: $count';
  }

  @override
  String get pinBlocked =>
      'The PIN was blocked after too many wrong attempts. Sign in with a WhatsApp code.';

  @override
  String get pinDelete => 'Delete digit';

  @override
  String pinProgress(int entered, int total) {
    return '$entered of $total digits';
  }

  @override
  String get accountAppLock => 'App lock';

  @override
  String get accountLockBiometrics => 'Fingerprint or face';

  @override
  String get accountLockPin => 'PIN';

  @override
  String get orderStatusReceived => 'Received';

  @override
  String get orderStatusPreparing => 'Being prepared';

  @override
  String get orderStatusShipped => 'Shipped';

  @override
  String get orderStatusDelivered => 'Delivered';

  @override
  String get orderStatusCancelled => 'Cancelled';

  @override
  String get orderAwaitingPayment => 'Awaiting payment';

  @override
  String get orderAwaitingPaymentNote =>
      'This order has not been paid, and it will not be prepared until it is. You can cancel it if you no longer want it.';

  @override
  String get orderStepReceived => 'Received';

  @override
  String get orderStepPreparing => 'Preparing';

  @override
  String get orderStepShipping => 'Shipping';

  @override
  String get orderStepDelivery => 'Delivery';

  @override
  String get orderPaymentCod => 'Cash on delivery';

  @override
  String get orderPaymentCard => 'Card';

  @override
  String get orderPaymentPoints => 'Points';

  @override
  String get ordersLoadFailed => 'Could not load your orders';

  @override
  String get ordersEmpty => 'No orders yet';

  @override
  String get ordersEmptyBody =>
      'Your orders will appear here after your first purchase.';

  @override
  String get ordersFilterActive => 'In progress';

  @override
  String get ordersFilterCompleted => 'Completed';

  @override
  String get ordersNoneInFilter => 'No orders in this group.';

  @override
  String get orderDetailsTitle => 'Order details';

  @override
  String orderItemQuantity(int count) {
    return 'Quantity $count';
  }

  @override
  String get orderItemFree => 'Free';

  @override
  String get orderCancel => 'Cancel order';

  @override
  String get orderCancelNote =>
      'Possible before shipping; your points and reward are returned automatically.';

  @override
  String get orderCancelConfirmTitle => 'Cancel this order?';

  @override
  String get orderCancelKeep => 'Keep the order';

  @override
  String get timeNow => 'Now';

  @override
  String timeMinutesAgo(int count) {
    return '$count min ago';
  }

  @override
  String timeHoursAgo(int count) {
    return '$count h ago';
  }

  @override
  String get notificationsMarkAllRead => 'Mark all as read';

  @override
  String get notificationsLoadFailed => 'Could not load notifications';

  @override
  String get notificationsEmpty => 'No notifications';

  @override
  String get notificationsEmptyBody =>
      'Updates about your orders, points and our offers will appear here.';

  @override
  String get notificationUnread => 'Unread';

  @override
  String accountNewCount(int count) {
    return '$count new';
  }

  @override
  String get view => 'View';

  @override
  String get legalTerms => 'Terms';

  @override
  String get legalPrivacy => 'Privacy policy';

  @override
  String get legalLoadFailed => 'Could not load the page';

  @override
  String get updateTitle => 'A new version is available';

  @override
  String get updateBody =>
      'This version is no longer supported. Update the app to keep using your wallet and orders.';

  @override
  String updateVersions(String current, String minimum) {
    return 'Your version $current · Required $minimum';
  }

  @override
  String get updateNow => 'Update now';

  @override
  String get updateStoreFailed =>
      'Could not open the store. Update the app from Google Play or the App Store.';

  @override
  String get assistantTitle => 'MAQAM assistant';

  @override
  String get assistantSubtitle => 'An automated helper for general questions';

  @override
  String get assistantGreeting => 'Hello! How can I help you?';

  @override
  String get assistantTyping => 'The assistant is typing';

  @override
  String get assistantHint => 'Type your question...';

  @override
  String get assistantSend => 'Send';

  @override
  String get assistantSuggestPoints => 'How do I earn points?';

  @override
  String get assistantSuggestCancel => 'Cancelling an order';

  @override
  String get assistantSuggestReturns => 'Returns policy';

  @override
  String assistantMessagesLeft(int count) {
    return '$count messages left today';
  }

  @override
  String get save => 'Save';

  @override
  String get accountEditName => 'Edit name';

  @override
  String get accountNameLabel => 'Full name';

  @override
  String get accountNameRequired => 'Enter your name (at least two letters).';

  @override
  String get accountNameSaved => 'Your name was saved.';

  @override
  String get deleteTitle => 'Are you sure?';

  @override
  String get deleteSubtitle => 'Deleting is permanent and cannot be undone.';

  @override
  String get deleteLostTitle => 'Deleted for good';

  @override
  String get deleteLostIdentity => 'Your name, phone number and email';

  @override
  String get deleteLostPoints => 'Your points balance and rank';

  @override
  String deleteLostPointsCount(int points) {
    return 'Your points balance ($points points) and rank';
  }

  @override
  String get deleteLostRewards => 'Your unused rewards';

  @override
  String get deleteLostAddresses => 'Your addresses, notifications and cart';

  @override
  String get deleteKeptTitle => 'Kept without your personal details';

  @override
  String get deleteKeptOrders => 'Your past orders, for accounting only.';

  @override
  String get deleteConfirmPrefix => 'To confirm, type ';

  @override
  String get deleteSubmit => 'Delete my account for good';

  @override
  String get deleteKeep => 'Go back';

  @override
  String get deleteDone => 'Your account was deleted.';

  @override
  String get suspendedTitle => 'Account temporarily suspended';

  @override
  String get suspendedBody =>
      'We noticed unusual scanning activity, so we paused the account for review to protect your points. Contact support to have it reactivated.';

  @override
  String get suspendedContact => 'Contact support';

  @override
  String get suspendedSupportMessage =>
      'Hello, my MAQAM app account is suspended and I would like it reviewed.';

  @override
  String get suspendedSupportFailed =>
      'Could not open WhatsApp. Email us at support@maqam-eg.com.';

  @override
  String get changePhoneTitle => 'Change phone number';

  @override
  String get changePhoneSubtitle =>
      'Enter your new number. You will send a code from it on WhatsApp to prove it is yours.';

  @override
  String get changePhoneLabel => 'New phone number';

  @override
  String get changePhoneDone => 'Your phone number was changed.';

  @override
  String get listSeparator => ', ';

  @override
  String get addressesLoadFailed => 'Could not load your addresses';

  @override
  String get addressesEmpty =>
      'No saved addresses yet. Add one to use it at checkout.';

  @override
  String get addressDefault => 'Default';

  @override
  String get addressEdit => 'Edit';

  @override
  String get addressMakeDefault => 'Make default';

  @override
  String get addressDelete => 'Delete';

  @override
  String get addressDeleteTitle => 'Delete this address?';

  @override
  String get addressDeleteBody =>
      'It will no longer appear in your addresses. Past orders keep their address.';

  @override
  String get addressDeleted => 'The address was deleted.';

  @override
  String get addressSaved => 'The address was saved.';

  @override
  String get addressDefaultSet => 'This is now your default address.';

  @override
  String get addressNewTitle => 'New address';

  @override
  String get addressEditTitle => 'Edit address';

  @override
  String get addressRecipient => 'Recipient name';

  @override
  String get addressPhoneInvalid => 'Invalid number';

  @override
  String get addressSave => 'Save address';

  @override
  String get addressSaveChanges => 'Save changes';

  @override
  String pointsAmount(String points) {
    return '$points points';
  }

  @override
  String get rewardsAvailable => 'Available';

  @override
  String get rewardsUsed => 'Used';

  @override
  String get rewardsExpired => 'Expired';

  @override
  String get rewardsLoadFailed => 'Could not load your rewards';

  @override
  String get rewardsEmpty => 'No rewards here';

  @override
  String get rewardsEmptyBody =>
      'Your lucky wheel prizes and personal coupons appear here.';

  @override
  String get rewardsNote =>
      'One code per order. If you cancel the order, the reward comes back here automatically.';

  @override
  String get rewardsTryWheel => 'Try your luck on the wheel';

  @override
  String rewardDiscount(String amount) {
    return '$amount off your order';
  }

  @override
  String rewardProduct(String name) {
    return 'Free gift: $name';
  }

  @override
  String get rewardProductFallback => 'a product';

  @override
  String get rewardFromWheel => 'From the lucky wheel';

  @override
  String get rewardFromMaqam => 'From MAQAM';

  @override
  String get rewardShipsFree => 'Ships free with any order';

  @override
  String get rewardNoExpiry => 'No expiry date';

  @override
  String rewardExpiresIn(int days) {
    return 'Expires in $days days';
  }

  @override
  String rewardUsedOn(String date) {
    return 'Used $date';
  }

  @override
  String rewardExpiredOn(String date) {
    return 'Expired $date';
  }

  @override
  String get rewardUseInCart => 'Use in cart';

  @override
  String get wheelLoadFailed => 'Could not load the wheel';

  @override
  String get wheelDisabled =>
      'The lucky wheel is paused for now. Try again later.';

  @override
  String wheelSemantics(String slices) {
    return 'Lucky wheel: $slices';
  }

  @override
  String get wheelCost => 'Cost per spin';

  @override
  String get wheelBalanceAfter => 'Balance after the spin';

  @override
  String get wheelSpin => 'Spin the wheel';

  @override
  String get wheelSpinning => 'Spinning…';

  @override
  String wheelNotEnough(String points) {
    return 'You need at least $points points to spin.';
  }

  @override
  String get wheelFooterPrefix => 'Prizes go to ';

  @override
  String get wheelFooterSuffix => ' and are used with their code in the cart.';

  @override
  String get wheelWonTitle => 'Congratulations!';

  @override
  String wheelWonBody(String prize) {
    return 'You won $prize.';
  }

  @override
  String get wheelLostTitle => 'Better luck next time';

  @override
  String get wheelLostBody => 'No prize this time. Try again.';

  @override
  String get wheelOk => 'OK';

  @override
  String get merchantDashboardTitle => 'Merchant dashboard';

  @override
  String get merchantLoadFailed => 'Could not load your merchant profile';

  @override
  String get merchantBenefitCode => 'You get a merchant code of your own';

  @override
  String get merchantBenefitPoints =>
      'You earn points each time a customer scans a code and picks you';

  @override
  String get merchantBenefitReview =>
      'The MAQAM team reviews your request and notifies you of the result';

  @override
  String get merchantNameLabel => 'Business name';

  @override
  String get merchantNameHint => 'e.g. Al Nour Showroom';

  @override
  String get merchantAddressLabel => 'Shop address';

  @override
  String get merchantAddressHint => 'Governorate, city, street';

  @override
  String get merchantLogoLabel => 'Shop logo';

  @override
  String get merchantLogoPick => 'Upload an image';

  @override
  String get merchantLogoChosen => 'Image chosen';

  @override
  String get merchantLogoHint => 'JPG or PNG up to 2MB';

  @override
  String get merchantSubmit => 'Send request';

  @override
  String get merchantSubmitNote =>
      'The code does not work in scans until MAQAM approves it.';

  @override
  String get merchantApplied =>
      'Request sent. You will be notified after the review.';

  @override
  String get merchantApproved => 'Approved merchant';

  @override
  String get merchantPending => 'Under review';

  @override
  String get merchantCodeLabel => 'Merchant code';

  @override
  String get merchantShare => 'Share';

  @override
  String merchantShareText(String name, String code) {
    return 'Bought from $name? Enter merchant code $code when you scan a MAQAM pack.';
  }

  @override
  String get merchantCodeCopied => 'Merchant code copied.';

  @override
  String get merchantCodeHint =>
      'Ask customers to enter this code when they scan any pack bought from you.';

  @override
  String get merchantPendingHint =>
      'Your request is under review. The code will work once approved, and you will be notified.';

  @override
  String get merchantLogoPlaceholder => 'Logo';

  @override
  String get merchantChangeLogo => 'Change shop logo';

  @override
  String get merchantStatPoints => 'Your total points';

  @override
  String get merchantStatScans => 'Scans';

  @override
  String get merchantStatMonth => 'Scans this month';

  @override
  String get merchantStatCustomers => 'Different customers';

  @override
  String get merchantPointsNote =>
      'Merchant points are a running total for display for now, and you cannot use your own code in your own scans.';

  @override
  String get scanOfflineBanner =>
      'No connection: scans are saved on your phone for now';

  @override
  String get scanOfflineSaved => 'Scan saved';

  @override
  String get scanOfflineExplain =>
      'Your points are not added yet. We will upload your scans and add the points as soon as you are back online.';

  @override
  String scanCodeTail(String tail) {
    return 'Code $tail ••••';
  }

  @override
  String get scanPendingBadge => 'Pending';

  @override
  String scanPendingCount(int count) {
    return 'Pending ($count)';
  }

  @override
  String get pendingTitle => 'Offline scans';

  @override
  String get pendingEmpty => 'No scans waiting';

  @override
  String get pendingEmptyBody =>
      'Scans made without a connection wait here until they are uploaded.';

  @override
  String pendingCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count scans waiting to upload',
      one: '1 scan waiting to upload',
    );
    return '$_temp0';
  }

  @override
  String get pendingAuto => 'Uploaded automatically when you are back online';

  @override
  String get pendingUploadNow => 'Upload now';

  @override
  String get pendingStillOffline =>
      'Still no connection. We will try again automatically.';

  @override
  String get pendingWaiting => 'Waiting';

  @override
  String get pendingLastUpload => 'Last upload';

  @override
  String pendingRejected(String reason) {
    return 'Not accepted: $reason';
  }

  @override
  String get pendingAlreadyCredited => 'Added';

  @override
  String get pendingHide => 'Hide';

  @override
  String get pendingFooter =>
      'Scans are saved on this phone only. Do not delete the app before they upload.';

  @override
  String get addressStreetDetail => 'Street, building, floor';

  @override
  String get wheelLocked => 'The lucky wheel is locked';

  @override
  String get seminarTitle => 'Seminar';

  @override
  String get seminarLoadFailed => 'Could not load the seminar';

  @override
  String get seminarLive => 'Live now';

  @override
  String get seminarEnded => 'Ended';

  @override
  String seminarEndsIn(String time) {
    return 'Ends in $time';
  }

  @override
  String seminarDays(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count days',
      one: '1 day',
    );
    return '$_temp0';
  }

  @override
  String seminarHours(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count hours',
      one: '1 hour',
    );
    return '$_temp0';
  }

  @override
  String seminarMinutes(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count minutes',
      one: '1 minute',
    );
    return '$_temp0';
  }

  @override
  String seminarPromoBody(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'A draw for $count prizes',
      one: 'A draw for 1 prize',
    );
    return '$_temp0';
  }

  @override
  String seminarRegisteredCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count signed up',
      one: '1 signed up',
      zero: 'Be the first to sign up',
    );
    return '$_temp0';
  }

  @override
  String get seminarJoin => 'Join the draw';

  @override
  String get seminarYouAreIn => 'You\'re in';

  @override
  String get seminarRegisteredDone => 'You are in the draw. Good luck!';

  @override
  String get seminarRegisteredLive =>
      'You are in the draw. Follow the results here.';

  @override
  String get seminarRegisteredEnded => 'You took part in this draw.';

  @override
  String get seminarClosed => 'Sign-up for this seminar is closed.';

  @override
  String get seminarHowItWorks =>
      'Sign up during the seminar; winners are drawn at random. Each person wins one prize at most.';

  @override
  String get seminarPrizes => 'Prizes and winners';

  @override
  String get seminarNotDrawn => 'Not drawn yet';

  @override
  String seminarWinner(String name) {
    return 'Winner: $name';
  }

  @override
  String get seminarWinnerYou => 'Winner: you!';

  @override
  String seminarYouWon(String prize) {
    return 'Congratulations! You won $prize';
  }

  @override
  String get seminarCollect => 'Contact the MAQAM team to collect your prize.';

  @override
  String get lockWithFaceId => 'with Face ID';

  @override
  String get lockWithFace => 'with face unlock';

  @override
  String get lockWithFingerprint => 'with your fingerprint';

  @override
  String biometricSetupBodyWith(String method) {
    return 'Sign in $method instead of typing your number and password, even after signing out.';
  }

  @override
  String biometricSetupEnableWith(String method) {
    return 'Turn on sign-in $method';
  }

  @override
  String unlockButtonWith(String method) {
    return 'Sign in $method';
  }

  @override
  String get unlockHintFace => 'Look at your phone to sign in';

  @override
  String get unlockHintFingerprint => 'Touch the fingerprint sensor to sign in';

  @override
  String get unlockBrowseAsGuest => 'Browse as a guest';

  @override
  String get merchantRecentScans => 'Latest scans with your code';

  @override
  String get merchantViewAll => 'View all';

  @override
  String get merchantNoScans =>
      'No one has used your code yet. Share it with your customers to start earning.';

  @override
  String get merchantScansTitle => 'Scans with your code';

  @override
  String get merchantScansLoadFailed => 'Could not load the scans';

  @override
  String merchantScanPoints(int points) {
    return '+$points pts';
  }

  @override
  String get merchantStatMonthPoints => 'Points this month';

  @override
  String get merchantStatTotalScans => 'Total scans';

  @override
  String get merchantStatTotalPoints => 'Total points';

  @override
  String get merchantDashboard => 'Merchant dashboard';

  @override
  String get merchantTechnicians => 'Technicians';

  @override
  String get merchantTechnicianScans => 'Scans';

  @override
  String get merchantTechnicianPoints => 'Points';

  @override
  String get merchantTechnicianBalance => 'Balance';

  @override
  String get merchantDeductPoints => 'Deduct points';

  @override
  String get merchantDeductAmount => 'Amount';

  @override
  String get merchantDeductAmountHint => 'Number of points to deduct';

  @override
  String get merchantDeductReason => 'Reason';

  @override
  String get merchantDeductReasonHint => 'e.g. product return';

  @override
  String get merchantDeductSubmit => 'Deduct';

  @override
  String get merchantDeductSuccess => 'Points deducted successfully.';

  @override
  String get merchantDeductAmountRequired => 'Enter the amount.';

  @override
  String get merchantDeductReasonRequired => 'Enter the reason.';

  @override
  String get merchantTransactions => 'Transactions';

  @override
  String get merchantTransactionEarned => 'Earned';

  @override
  String get merchantTransactionDeducted => 'Deducted';

  @override
  String get merchantNoTechnicians =>
      'No technicians bound to your merchant yet.';

  @override
  String get merchantNoTransactions => 'No transactions yet.';

  @override
  String get merchantLoadMore => 'Load more';

  @override
  String get merchantUnknownCustomer => 'Customer';

  @override
  String merchantSince(String date) {
    return 'Merchant since $date';
  }

  @override
  String merchantLastScan(String time) {
    return 'Last scan: $time';
  }

  @override
  String get accountPointsBalance => 'Points balance';

  @override
  String get accountRank => 'Rank';

  @override
  String accountToNextRank(String points, String rank) {
    return '$points points to reach $rank';
  }

  @override
  String get accountTopRank => 'You reached the top rank';

  @override
  String get accountMerchantOpen => 'Merchant dashboard';

  @override
  String get accountMerchantPendingTitle =>
      'Your merchant request is under review';

  @override
  String get accountCopyCode => 'Copy code';
}
