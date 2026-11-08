import 'dart:async';

import 'package:flutter/material.dart';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/app/app.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/core/push/push_messaging.dart';
import 'package:maqam_app/core/security/app_lock.dart';
import 'package:maqam_app/core/security/biometric_authenticator.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/core/session/quick_sign_in.dart';
import 'package:maqam_app/core/session/session_provider.dart';
import 'package:maqam_app/core/storage/key_value_store.dart';
import 'package:maqam_app/features/account/data/api_account_repository.dart';
import 'package:maqam_app/features/account/domain/account_repository.dart';
import 'package:maqam_app/features/addresses/data/api_address_book_repository.dart';
import 'package:maqam_app/features/addresses/domain/address_book.dart';
import 'package:maqam_app/features/auth/data/api_auth_repository.dart';
import 'package:maqam_app/features/auth/data/phone_change_repository.dart';
import 'package:maqam_app/features/auth/domain/auth_repository.dart';
import 'package:maqam_app/features/auth/domain/whatsapp_challenge.dart';
import 'package:maqam_app/features/cart/data/api_cart_repository.dart';
import 'package:maqam_app/features/cart/domain/cart.dart';
import 'package:maqam_app/features/cart/presentation/payment_screen.dart';
import 'package:maqam_app/features/catalog/data/api_catalog_repository.dart';
import 'package:maqam_app/features/catalog/domain/catalog_repository.dart';
import 'package:maqam_app/features/catalog/domain/category.dart';
import 'package:maqam_app/features/catalog/domain/product.dart';
import 'package:maqam_app/features/catalog/domain/product_details.dart';
import 'package:maqam_app/features/catalog/domain/promo_banner.dart';
import 'package:maqam_app/features/content/data/api_content_repository.dart';
import 'package:maqam_app/features/content/domain/content.dart';
import 'package:maqam_app/features/loyalty/data/api_loyalty_repository.dart';
import 'package:maqam_app/features/loyalty/domain/wallet.dart';
import 'package:maqam_app/features/merchant/data/api_merchant_repository.dart';
import 'package:maqam_app/features/merchant/data/logo_picker.dart';
import 'package:maqam_app/features/merchant/domain/merchant.dart';
import 'package:maqam_app/features/notifications/data/api_notifications_repository.dart';
import 'package:maqam_app/features/notifications/domain/app_notification.dart';
import 'package:maqam_app/features/orders/data/api_orders_repository.dart';
import 'package:maqam_app/features/orders/domain/order.dart';
import 'package:maqam_app/features/scan/data/api_scan_repository.dart';
import 'package:maqam_app/features/scan/domain/scan.dart';
import 'package:maqam_app/features/scan/presentation/camera_view.dart';
import 'package:maqam_app/features/seminars/data/api_seminar_repository.dart';
import 'package:maqam_app/features/seminars/domain/seminar.dart';

class FakeCatalog implements CatalogRepository {
  FakeCatalog({this.fail = false});

  final bool fail;
  final requestedCategoryIds = <int?>[];
  final requestedSearches = <String>[];

  @override
  Future<List<Category>> categories() async => const [Category(id: 7, name: 'مفاتيح')];

  @override
  Future<ProductDetails> product(int id) async {
    if (fail) throw const ApiException(ApiErrorKind.server);
    return ProductDetails(
      product: Product(
        id: id,
        name: 'مفتاح تجريبي',
        price: 75,
        inStock: id != 2,
        stock: id == 2 ? 0 : 3,
        categoryId: 7,
        categoryName: 'قسم',
      ),
      sku: 'MQM-TEST-01',
      description: 'وصف تجريبي للمنتج.',
      weight: '0.25',
      dimensions: '86 x 86 mm',
      colors: const [
        ProductColor(name: 'أسود', hex: '#1A1A1A'),
        ProductColor(name: 'أبيض', hex: '#FFFFFF'),
      ],
      options: const [
        ProductOption(id: 11, name: 'المقاس', value: '48 مللي', price: 75),
        ProductOption(id: 12, name: 'المقاس', value: '60 مللي', price: 90),
      ],
    );
  }

  @override
  Future<List<PromoBanner>> banners() async => const [PromoBanner(id: 1, title: 'عرض تجريبي')];

  @override
  Future<ProductPage> products({
    int? categoryId,
    String search = '',
    ProductSort sort = ProductSort.newest,
    int perPage = 30,
  }) async {
    requestedCategoryIds.add(categoryId);
    requestedSearches.add(search);
    if (fail) throw const ApiException(ApiErrorKind.server);
    return const ProductPage(
      items: [
        Product(id: 1, name: 'مفتاح تجريبي', price: 75, categoryName: 'قسم'),
        Product(id: 2, name: 'بدون سعر', price: 0, inStock: false),
      ],
      total: 113,
    );
  }
}

/// A cart held in memory. Each unit costs 75, shipping is 35, and the code
/// `SAVE10` takes 10 off.
class FakeCart implements CartRepository {
  final lines = <CartItem>[];
  String? coupon;
  final savedAddresses = <Address>[];
  final added = <({int productId, int quantity, int? optionId, String? color})>[];

  /// What the last checkout was called with.
  ({int? addressId, NewAddress? newAddress, PaymentMethod payment, String notes})? placed;
  Uri? paymentUrl;

  /// What the server will say about an online payment once the customer is back.
  PaymentState paymentOutcome = PaymentState.paid;

  @override
  Future<PaymentState> paymentState(String orderNumber) async => paymentOutcome;

  Cart get _cart {
    final subtotal = lines.fold<double>(0, (sum, l) => sum + l.unitPrice * l.quantity);
    final discount = coupon == null ? 0.0 : 10.0;
    return Cart(
      items: List.of(lines),
      itemsCount: lines.fold(0, (sum, l) => sum + l.quantity),
      couponCode: coupon,
      subtotal: subtotal,
      discount: discount,
      shipping: lines.isEmpty ? 0 : 35,
      total: lines.isEmpty ? 0 : subtotal - discount + 35,
    );
  }

  @override
  Future<Cart> cart() async => _cart;

  @override
  Future<Cart> add({required int productId, int quantity = 1, int? optionId, String? color}) async {
    added.add((productId: productId, quantity: quantity, optionId: optionId, color: color));
    lines.add(CartItem(
      id: lines.length + 1,
      name: 'مفتاح تجريبي',
      quantity: quantity,
      unitPrice: 75,
      optionLabel: color,
    ));
    return _cart;
  }

  @override
  Future<Cart> setQuantity(int itemId, int quantity) async {
    final index = lines.indexWhere((l) => l.id == itemId);
    final line = lines.removeAt(index);
    if (quantity > 0) {
      lines.insert(
        index,
        CartItem(id: line.id, name: line.name, quantity: quantity, unitPrice: line.unitPrice),
      );
    }
    return _cart;
  }

  @override
  Future<Cart> applyCoupon(String code) async {
    if (code != 'SAVE10' && code != 'WD-9812AXYZ') {
      throw const ApiException(ApiErrorKind.validation, message: 'الكود غير صالح.');
    }
    coupon = code;
    return _cart;
  }

  @override
  Future<Cart> removeCoupon() async {
    coupon = null;
    return _cart;
  }

  @override
  Future<List<Address>> addresses() async => savedAddresses;

  @override
  Future<PlacedOrder> checkout({
    int? addressId,
    NewAddress? newAddress,
    required PaymentMethod payment,
    String notes = '',
  }) async {
    placed = (addressId: addressId, newAddress: newAddress, payment: payment, notes: notes);
    lines.clear();
    return PlacedOrder(
      orderNumber: 'MQ-TEST-0001',
      paymentUrl: payment == PaymentMethod.online ? paymentUrl : null,
      paymentReturnUrl: Uri.parse('https://maqam-eg.com/payment/kashier/callback'),
    );
  }
}

/// A device that hands out `push-token-1` and lets a test deliver messages.
class FakePush implements PushMessaging {
  final foreground = StreamController<PushMessage>.broadcast();
  final opened = StreamController<PushMessage>.broadcast();
  PushMessage? launch;

  @override
  Future<String?> start() async => 'push-token-1';

  @override
  Stream<String> get tokenRefreshes => const Stream.empty();

  @override
  Stream<PushMessage> get foregroundMessages => foreground.stream;

  @override
  Stream<PushMessage> get openedMessages => opened.stream;

  @override
  Future<PushMessage?> launchMessage() async => launch;
}

/// Two notifications: an unread order update and a read rank upgrade.
class FakeNotifications implements NotificationsRepository {
  final read = <int>[];
  bool allRead = false;
  final devices = <({String token, String? platform})>[];

  @override
  Future<NotificationFeed> feed() async => NotificationFeed(
        unreadCount: allRead || read.contains(1) ? 0 : 1,
        items: [
          AppNotification(
            id: 1,
            title: 'تحديث الطلب MQ-ACTIVE-1',
            body: 'طلبك جارٍ تجهيزه.',
            isRead: allRead || read.contains(1),
            orderNumber: 'MQ-ACTIVE-1',
            createdAt: DateTime.now(),
          ),
          AppNotification(
            id: 2,
            title: 'مبروك! رتبة جديدة',
            body: 'تمت ترقيتك إلى رتبة ذهبي.',
            isRead: true,
            createdAt: DateTime(2026, 9, 2),
          ),
        ],
      );

  @override
  Future<void> markRead(int id) async => read.add(id);

  @override
  Future<void> markAllRead() async => allRead = true;

  @override
  Future<void> registerDevice({required String token, String? platform}) async =>
      devices.add((token: token, platform: platform));
}

/// Two orders: one being prepared (cancellable) and one delivered.
class FakeOrders implements OrdersRepository {
  final cancelled = <String>[];

  Order _preparing() => Order(
        number: 'MQ-ACTIVE-1',
        status: cancelled.contains('MQ-ACTIVE-1') ? OrderStatus.cancelled : OrderStatus.preparing,
        total: 185,
        paymentMethod: 'cod',
        placedAt: DateTime.now(),
        canCancel: !cancelled.contains('MQ-ACTIVE-1'),
      );

  static final _delivered = Order(
    number: 'MQ-DONE-2',
    status: OrderStatus.delivered,
    total: 90,
    paymentMethod: 'wallet',
    placedAt: DateTime(2026, 9, 2),
  );

  /// A card payment that was started and never finished.
  static final _unpaid = Order(
    number: 'MQ-UNPAID-3',
    status: OrderStatus.received,
    total: 60,
    paymentMethod: 'kashier',
    placedAt: DateTime(2026, 9, 1),
    canCancel: true,
    awaitingPayment: true,
  );

  @override
  Future<List<Order>> orders() async => [_preparing(), _delivered, _unpaid];

  @override
  Future<OrderDetails> order(String number) async => OrderDetails(
        order: switch (number) {
          'MQ-DONE-2' => _delivered,
          'MQ-UNPAID-3' => _unpaid,
          _ => _preparing(),
        },
        subtotal: 150,
        shipping: 35,
        recipientName: 'أحمد محمود',
        recipientPhone: '01012345678',
        addressLine: 'القاهرة، مدينة نصر',
        items: const [
          OrderItem(name: 'مفتاح تجريبي', quantity: 2, lineTotal: 150, optionLabel: 'أسود'),
          OrderItem(name: 'هدية العجلة', quantity: 1, lineTotal: 0),
        ],
      );

  @override
  Future<OrderDetails> cancel(String number) async {
    cancelled.add(number);
    return order(number);
  }
}

/// Every code is worth 25 points except `BAD`, which the server rejects.
class FakeScan implements ScanRepository {
  final claimed = <({String serialCode, String? merchantCode})>[];

  /// While true every request fails as if the phone had no connection.
  bool offline = false;

  /// Set to make the next scan trip the fraud check.
  bool freezeNext = false;
  final uploaded = <List<QueuedScan>>[];

  @override
  Future<List<SyncedScan>> sync(List<QueuedScan> scans) async {
    if (offline) throw const ApiException(ApiErrorKind.network);
    uploaded.add(scans);
    return [
      for (final scan in scans)
        scan.serialCode == 'BAD'
            ? SyncedScan(scan: scan, status: SyncStatus.rejected, message: 'الكود ممسوح مسبقاً.')
            : SyncedScan(scan: scan, status: SyncStatus.credited, points: 25),
    ];
  }

  @override
  Future<ScanResult> scan({required String serialCode, String? merchantCode}) async {
    if (offline) throw const ApiException(ApiErrorKind.network);
    if (freezeNext) {
      return const ScanResult(pointsAwarded: 25, newBalance: 425, accountFrozen: true);
    }
    if (serialCode == 'BAD') {
      throw const ApiException(ApiErrorKind.validation, message: 'الكود ممسوح مسبقاً.');
    }
    claimed.add((serialCode: serialCode, merchantCode: merchantCode));
    return ScanResult(
      pointsAwarded: 25,
      newBalance: 425,
      merchantName: merchantCode == null ? null : 'معرض الأمل',
      merchantPoints: merchantCode == null ? 0 : 5,
    );
  }

  @override
  Future<List<RecentMerchant>> recentMerchants() async =>
      const [RecentMerchant(code: 'M-AMAL', name: 'معرض الأمل')];
}

/// Stands in for the gateway's page: a button that "comes back" from it.
Widget fakePaymentPage({
  required Uri paymentUrl,
  required Uri? returnUrl,
  required VoidCallback onReturned,
}) {
  return Center(
    child: TextButton(onPressed: onReturned, child: Text('gateway page $paymentUrl')),
  );
}

/// Stands in for the camera: a button that "sees" [code] when tapped.
Widget fakeCamera({
  required ValueChanged<String> onCode,
  required bool torchOn,
  required bool paused,
}) {
  return Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (final code in ['MQ-SERIAL-1', 'https://maqam-eg.com/q/MQ-SERIAL-2', 'BAD'])
          TextButton(onPressed: () => onCode(code), child: Text('camera sees $code')),
      ],
    ),
  );
}

class FakeLoyalty implements LoyaltyRepository {
  final requestedFilters = <TransactionFilter>[];
  int spins = 0;

  /// Makes the rewards list fail to load.
  bool failRewards = false;

  @override
  Future<List<Reward>> rewards(RewardStatus status) async {
    if (failRewards) throw const ApiException(ApiErrorKind.server);
    return _rewards(status);
  }

  List<Reward> _rewards(RewardStatus status) => switch (status) {
        RewardStatus.available => [
            Reward(
              id: 1,
              code: 'WD-9812AXYZ',
              kind: RewardKind.discount,
              fromWheel: true,
              isPercentage: true,
              amount: 10,
              expiresAt: DateTime.now().add(const Duration(days: 12)),
            ),
            const Reward(
              id: 2,
              code: 'GP-4471KLMN',
              kind: RewardKind.product,
              fromWheel: true,
              productName: 'مفتاح تجريبي',
            ),
          ],
        RewardStatus.used => [
            Reward(id: 3, code: 'CP-USED0001', kind: RewardKind.coupon, amount: 50, usedAt: DateTime(2026, 9, 2)),
          ],
        RewardStatus.expired => const [],
      };

  /// False for a customer without enough points to spin.
  bool canSpin = true;

  @override
  Future<WheelConfig> wheel() async => WheelConfig(
        enabled: true,
        cost: 50,
        balance: canSpin ? 400 : 20,
        canSpin: canSpin,
        slices: const [
          WheelSlice(label: 'منتج هدية', isPrize: true),
          WheelSlice(label: 'حظ أوفر', isPrize: false),
          WheelSlice(label: '50 نقطة', isPrize: true),
          WheelSlice(label: 'خصم 10%', isPrize: true),
          WheelSlice(label: 'كوبون', isPrize: true),
          WheelSlice(label: '100 نقطة', isPrize: true),
        ],
      );

  /// Every spin wins 50 points.
  @override
  Future<SpinOutcome> spin() async {
    spins++;
    return const SpinOutcome(won: true, sliceIndex: 2, newBalance: 400, prizeLabel: '50 نقطة');
  }

  @override
  Future<List<LeaderboardEntry>> leaderboard() async => const [
        LeaderboardEntry(rank: 1, name: 'أحمد', points: 5400),
        LeaderboardEntry(rank: 2, name: 'محمد', points: 5000),
        LeaderboardEntry(rank: 3, name: 'سارة', points: 4800),
      ];

  @override
  Future<Wallet> wallet() async => const Wallet(
        balance: 400,
        totalEarned: 450,
        progress: 0.45,
        pointsToNextRank: 550,
        rankName: 'فضي',
        rankIconUrl: null,
        nextRankName: 'ذهبي',
        nextRankMinPoints: 1000,
      );

  @override
  Future<void> acknowledgeRankUp() async {}

  @override
  Future<List<PointsTransaction>> transactions({
    TransactionFilter filter = TransactionFilter.all,
  }) async {
    requestedFilters.add(filter);
    return [
      PointsTransaction(id: 1, points: 25, description: 'مسح كود', date: DateTime.now()),
      const PointsTransaction(id: 2, points: -50, description: 'تكلفة عجلة الحظ'),
    ];
  }
}

/// The customer's message "arrives" on the second poll; the code is 123456.
class FakeAuth implements AuthRepository {
  String? startedPhone;
  int checks = 0;
  bool signedOut = false;

  /// Phone -> password for accounts that sign in with one.
  final passwordAccounts = <String, String>{};

  /// Whether the account that signs in over WhatsApp already has a password.
  bool whatsappAccountHasPassword = true;

  String? completedName;
  String? savedPassword;

  @override
  Future<bool> hasPassword(String phone) async => passwordAccounts.containsKey(phone);

  @override
  Future<SignIn> signInWithPassword({required String phone, required String password}) async {
    if (passwordAccounts[phone] != password) {
      throw const ApiException(
        ApiErrorKind.validation,
        message: 'رقم الجوال أو كلمة المرور غير صحيحة.',
        code: 'INVALID_CREDENTIALS',
      );
    }
    return SignIn(
      session: AuthSession(token: 'password-token', fullName: 'أحمد علي', phone: phone, boundMerchantId: 1, boundMerchantName: 'Test Merchant'),
      hasPassword: true,
    );
  }

  @override
  Future<void> completeProfile({required String fullName, required String password, String? governorate, String? area}) async {
    completedName = fullName;
    savedPassword = password;
  }

  @override
  Future<void> setPassword(String password) async => savedPassword = password;

  @override
  Future<WhatsappChallenge> startChallenge(String phone) async {
    startedPhone = phone;
    return WhatsappChallenge(
      id: 'challenge-1',
      code: 'MAQAM-482913',
      whatsappUrl: Uri.parse('https://wa.me/201000000000?text=MAQAM-482913'),
      validFor: const Duration(minutes: 10),
      pollInterval: const Duration(seconds: 4),
    );
  }

  @override
  Future<Duration?> checkChallenge(String challengeId) async =>
      ++checks < 2 ? null : const Duration(minutes: 5);

  @override
  Future<SignIn> verifyOtp({required String challengeId, required String otp}) async {
    if (otp != '123456') {
      throw const ApiException(
        ApiErrorKind.validation,
        message: 'كود الدخول غير صحيح.',
        code: ApiErrorCodes.invalidOtp,
      );
    }
    return SignIn(
      session: const AuthSession(token: 'token-1', phone: '01012345678', boundMerchantId: 1, boundMerchantName: 'Test Merchant'),
      hasPassword: whatsappAccountHasPassword,
    );
  }

  String? signedOutPushToken;

  @override
  Future<void> signOut({String? pushToken}) async {
    signedOut = true;
    signedOutPushToken = pushToken;
  }

  /// Secrets the server would still accept for quick sign-in.
  final quickSecrets = <String>{};
  int quickSignIns = 0;

  @override
  Future<String> enableQuickSignIn() async {
    final secret = 'quick-secret-${quickSecrets.length + 1}';
    quickSecrets.add(secret);
    return secret;
  }

  @override
  Future<SignIn> quickSignIn(String secret) async {
    if (!quickSecrets.contains(secret)) {
      throw const ApiException(
        ApiErrorKind.validation,
        message: 'لم يعد الدخول السريع متاحاً على هذا الهاتف.',
        code: ApiErrorCodes.quickLoginInvalid,
      );
    }
    quickSignIns++;
    return const SignIn(
      session: AuthSession(token: 'quick-token', fullName: 'أحمد علي', phone: '01012345678', boundMerchantId: 1, boundMerchantName: 'Test Merchant'),
      hasPassword: true,
    );
  }

  @override
  Future<void> disableQuickSignIn() async => quickSecrets.clear();
}

/// Static content, the version check and an assistant that always knows the
/// shipping cost. The question `FAIL` gets the server refusing.
class FakeContent implements ContentRepository {
  FakeContent({this.updateRequired = false});

  final bool updateRequired;
  final questions = <({String message, List<ChatTurn> history})>[];

  @override
  Future<LegalPage> legalPage(LegalPageSlug slug) async => LegalPage(
        title: slug == LegalPageSlug.terms ? 'الشروط والأحكام' : 'سياسة الخصوصية',
        lead: 'آخر تحديث 2026',
        blocks: const [
          LegalBlock(LegalBlockKind.heading, 'المنتجات والطلبات'),
          LegalBlock(LegalBlockKind.bullet, 'الأسعار بالجنيه المصري.'),
        ],
      );

  @override
  Future<AppUpdateStatus> checkForUpdate({required String platform, required String version}) async =>
      AppUpdateStatus(
        required: updateRequired,
        currentVersion: version,
        minimumVersion: updateRequired ? '9.0.0' : version,
        storeUrl: Uri.parse('https://play.google.com/store/apps/details?id=maqam.egypt'),
      );

  @override
  Future<String?> supportWhatsapp() async => '201001234567';

  /// What the dashboard changed; tests set it before the app starts.
  Map<String, Map<String, String>> texts = const {};

  @override
  Future<Map<String, Map<String, String>>> appTexts() async => texts;

  @override
  Future<ChatReply> ask(String message, {List<ChatTurn> history = const []}) async {
    if (message == 'FAIL') {
      throw const ApiException(ApiErrorKind.server, message: 'المساعد غير متاح حالياً.');
    }
    questions.add((message: message, history: history));
    return const ChatReply(text: 'الشحن 35 جنيهاً للطلب.', messagesLeftToday: 59);
  }
}

class FakeAccount implements AccountRepository {
  String? savedName;
  bool deleted = false;

  @override
  Future<void> updateName(String fullName) async => savedName = fullName;

  @override
  Future<void> deleteAccount() async => deleted = true;

  @override
  Future<({int id, String name})> bindMerchant(String merchantCode) async =>
      (id: 1, name: 'Test Merchant');

  @override
  Future<void> requestMerchantChange(String merchantCode) async {}

  @override
  Future<String?> merchantChangeStatus() async => null;
}

/// One saved address to start with.
class FakeAddressBook implements AddressBookRepository {
  bool fail = false;
  final saved = <Address>[
    const Address(
      id: 1,
      recipientName: 'أحمد محمود',
      phone: '01012345678',
      governorate: 'القاهرة',
      city: 'مدينة نصر',
      street: 'شارع الطيران عمارة 12',
      isDefault: true,
    ),
  ];

  @override
  Future<List<Address>> addresses() async {
    if (fail) throw const ApiException(ApiErrorKind.network);
    return List.of(saved);
  }

  @override
  Future<void> add(AddressDraft draft) async => saved.add(_address(saved.length + 10, draft, saved.isEmpty));

  @override
  Future<void> update(int id, AddressDraft draft, {bool makeDefault = false}) async {
    final index = saved.indexWhere((a) => a.id == id);
    if (makeDefault) {
      for (final (i, a) in saved.indexed) {
        saved[i] = _address(a.id, AddressDraft.of(a), false);
      }
    }
    saved[index] = _address(id, draft, makeDefault || saved[index].isDefault);
    saved.sort((a, b) => (b.isDefault ? 1 : 0) - (a.isDefault ? 1 : 0));
  }

  @override
  Future<void> delete(int id) async => saved.removeWhere((a) => a.id == id);

  static Address _address(int id, AddressDraft draft, bool isDefault) => Address(
        id: id,
        recipientName: draft.recipientName,
        phone: draft.phone,
        governorate: draft.governorate,
        city: draft.city,
        street: draft.street,
        isDefault: isDefault,
      );
}

/// No merchant profile until the customer applies; then one under review.
class FakeMerchant implements MerchantRepository {
  MerchantProfile? current;
  ({String name, String? address, LogoImage? logo})? applied;
  bool fail = false;

  @override
  Future<MerchantProfile?> profile() async {
    if (fail) throw const ApiException(ApiErrorKind.server);
    return current;
  }

  @override
  Future<MerchantProfile> apply({required String businessName, String? businessAddress, LogoImage? logo}) async {
    applied = (name: businessName, address: businessAddress, logo: logo);
    return current = MerchantProfile(code: 'M-AB12CD34', businessName: businessName, approved: false);
  }

  @override
  Future<MerchantProfile> uploadLogo(LogoImage logo) async => current!;

  /// Pages handed out by [scans], first page first.
  List<List<MerchantScan>> scanPages = const [];

  @override
  Future<MerchantScanPage> scans({int page = 1}) async {
    if (fail) throw const ApiException(ApiErrorKind.server);
    final items = page <= scanPages.length ? scanPages[page - 1] : const <MerchantScan>[];
    return MerchantScanPage(items: items, hasMore: page < scanPages.length);
  }

  @override
  Future<List<Technician>> technicians() async => const [];

  @override
  Future<TechnicianDetail> technician(int id) async =>
      TechnicianDetail(id: id, fullName: 'Test', totalPoints: 0, totalScans: 0, currentBalance: 0);

  @override
  Future<MerchantScanPage> technicianScans(int technicianId, {int page = 1}) async =>
      const MerchantScanPage(items: [], hasMore: false);

  @override
  Future<void> deductPoints(int technicianId, {required int amount, required String reason}) async {}

  @override
  Future<List<MerchantTransaction>> transactions() async => const [];
}

/// The new number's message arrives at once, and the code is 654321.
class FakePhoneChange implements AuthRepository {
  String? startedPhone;

  @override
  Future<WhatsappChallenge> startChallenge(String phone) async {
    startedPhone = phone;
    return WhatsappChallenge(
      id: 'phone-challenge',
      code: 'MAQAM-111222',
      whatsappUrl: Uri.parse('https://wa.me/201000000000?text=MAQAM-111222'),
      validFor: const Duration(minutes: 10),
      pollInterval: const Duration(seconds: 4),
    );
  }

  @override
  Future<Duration?> checkChallenge(String challengeId) async => const Duration(minutes: 5);

  @override
  Future<SignIn> verifyOtp({required String challengeId, required String otp}) async {
    if (otp != '654321') {
      throw const ApiException(ApiErrorKind.validation, message: 'كود الدخول غير صحيح.');
    }
    return SignIn(
      session: AuthSession(token: 'saved', fullName: 'أحمد علي', phone: startedPhone, boundMerchantId: 1, boundMerchantName: 'Test Merchant'),
      hasPassword: true,
    );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

/// One seminar running now with three prizes; the first is already drawn.
class FakeSeminars implements SeminarRepository {
  /// False when no seminar is running.
  bool live = true;
  bool registered = false;

  /// Set once this customer has won the second prize.
  bool won = false;
  final registrations = <int>[];

  Seminar _seminar() => Seminar(
        id: 7,
        title: 'سيمينار القاهرة',
        description: 'لقاء مع فنيي الكهرباء.',
        location: 'فندق النيل',
        startsAt: DateTime.now().subtract(const Duration(hours: 1)),
        endsAt: DateTime.now().add(const Duration(hours: 2)),
        isLive: live,
        registrations: 41 + (registered ? 1 : 0),
        isRegistered: registered,
        draws: [
          const SeminarDraw(position: 1, prize: 'تليفزيون', isDrawn: true, winnerName: 'محمد •••321'),
          SeminarDraw(position: 2, prize: 'شنيور', isDrawn: won, winnerName: won ? 'أحمد •••678' : null, isMine: won),
          const SeminarDraw(position: 3, prize: 'طقم مفكات'),
        ],
      );

  @override
  Future<Seminar?> current() async => live ? _seminar() : null;

  @override
  Future<Seminar> seminar(int id) async => _seminar();

  @override
  Future<Seminar> register(int id) async {
    if (!live) {
      throw const ApiException(ApiErrorKind.validation, message: 'التسجيل في هذا السيمينار غير متاح الآن.');
    }
    registrations.add(id);
    registered = true;
    return _seminar();
  }
}

/// A device whose owner check gives the queued answers (default: confirmed).
class FakeBiometrics implements BiometricAuthenticator {
  FakeBiometrics({this.available = false, this.sensor = BiometricKind.fingerprint});

  final bool available;

  /// What the phone reports when [available].
  final BiometricKind sensor;

  @override
  Future<BiometricKind> kind() async => available ? sensor : BiometricKind.none;
  final answers = <bool>[];
  final reasons = <String>[];

  @override
  Future<bool> isAvailable() async => available;

  @override
  Future<bool> authenticate(String reason) async {
    reasons.add(reason);
    return answers.isEmpty ? true : answers.removeAt(0);
  }
}

class TestApp {
  TestApp(
    this.catalog,
    this.auth,
    this.store,
    this.biometrics,
    this.loyalty,
    this.cart,
    this.scan,
    this.orders,
    this.notifications,
    this.push,
    this.content,
    this.account,
    this.addressBook,
    this.merchant,
    this.phoneChange,
    this.seminars,
  );

  final FakeCatalog catalog;
  final FakeAuth auth;
  final InMemoryKeyValueStore store;
  final FakeBiometrics biometrics;
  final FakeLoyalty loyalty;
  final FakeCart cart;
  final FakeScan scan;
  final FakeOrders orders;
  final FakeNotifications notifications;
  final FakePush push;
  final FakeContent content;
  final FakeAccount account;
  final FakeAddressBook addressBook;
  final FakeMerchant merchant;
  final FakePhoneChange phoneChange;
  final FakeSeminars seminars;
}

Future<TestApp> pumpApp(
  WidgetTester tester, {
  bool failCatalog = false,
  AuthSession? session,
  bool biometricsAvailable = false,
  // Defaults to "already answered no" on a device without a sensor, so tests that
  // are not about the lock go straight into the app after signing in.
  BiometricPreference? biometricPreference,
  List<bool> biometricAnswers = const [],
  // A notification whose tap is what started the app.
  PushMessage? launchPush,
  // The server says this version is too old to run.
  bool updateRequired = false,
  // Wording changed from the dashboard, as {languageCode: {key: text}}.
  Map<String, Map<String, String>> appTexts = const {},
  // Whether a seminar is running.
  bool seminarLive = false,
  // A phone that remembers its customer for quick sign-in.
  QuickSignInCredential? quickSignIn,
  // Quick sign-in secrets the fake server accepts.
  Set<String> acceptedQuickSecrets = const {},
  BiometricKind sensor = BiometricKind.fingerprint,
  // A tall phone, so a whole tab is laid out without scrolling.
  Size surfaceSize = const Size(430, 1600),
}) async {
  tester.view.physicalSize = surfaceSize;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);

  final app = TestApp(
    FakeCatalog(fail: failCatalog),
    FakeAuth()..quickSecrets.addAll(acceptedQuickSecrets),
    InMemoryKeyValueStore(),
    FakeBiometrics(available: biometricsAvailable, sensor: sensor)..answers.addAll(biometricAnswers),
    FakeLoyalty(),
    FakeCart(),
    FakeScan(),
    FakeOrders(),
    FakeNotifications(),
    FakePush()..launch = launchPush,
    FakeContent(updateRequired: updateRequired)..texts = appTexts,
    FakeAccount(),
    FakeAddressBook(),
    FakeMerchant(),
    FakePhoneChange(),
    FakeSeminars()..live = seminarLive,
  );
  await tester.pumpWidget(
    ProviderScope(
      retry: (retryCount, error) => null,
      overrides: [
        loyaltyRepositoryProvider.overrideWithValue(app.loyalty),
        cartRepositoryProvider.overrideWithValue(app.cart),
        scanRepositoryProvider.overrideWithValue(app.scan),
        ordersRepositoryProvider.overrideWithValue(app.orders),
        notificationsRepositoryProvider.overrideWithValue(app.notifications),
        pushMessagingProvider.overrideWithValue(app.push),
        contentRepositoryProvider.overrideWithValue(app.content),
        accountRepositoryProvider.overrideWithValue(app.account),
        addressBookRepositoryProvider.overrideWithValue(app.addressBook),
        merchantRepositoryProvider.overrideWithValue(app.merchant),
        phoneChangeRepositoryProvider.overrideWithValue(app.phoneChange),
        seminarRepositoryProvider.overrideWithValue(app.seminars),
        logoPickerProvider.overrideWithValue(
          () async => const LogoImage(bytes: [1, 2, 3], fileName: 'logo.png'),
        ),
        cameraViewBuilderProvider.overrideWithValue(fakeCamera),
        paymentPageBuilderProvider.overrideWithValue(fakePaymentPage),
        inAppPaymentSupportedProvider.overrideWithValue(true),
        catalogRepositoryProvider.overrideWithValue(app.catalog),
        authRepositoryProvider.overrideWithValue(app.auth),
        keyValueStoreProvider.overrideWithValue(app.store),
        initialSessionProvider.overrideWithValue(session),
        initialQuickSignInProvider.overrideWithValue(quickSignIn),
        biometricAuthenticatorProvider.overrideWithValue(app.biometrics),
        initialBiometricPreferenceProvider.overrideWithValue(
          biometricPreference ??
              (biometricsAvailable ? BiometricPreference.undecided : BiometricPreference.declined),
        ),
      ],
      child: const MaqamApp(),
    ),
  );
  await tester.pumpAndSettle();
  return app;
}

Future<void> openLogin(WidgetTester tester) async {
  await tester.tap(find.text('تخطي'));
  await tester.pumpAndSettle();
}

/// Switches tab by tapping its label in the bottom bar.
Future<void> openTab(WidgetTester tester, String label) async {
  await tester.tap(find.text(label).last);
  await tester.pumpAndSettle();
}

/// Types [pin] on the app's number pad.
Future<void> typePin(WidgetTester tester, String pin) async {
  for (final digit in pin.split('')) {
    await tester.tap(find.widgetWithText(InkWell, digit));
    await tester.pump();
  }
  await tester.pumpAndSettle();
}
