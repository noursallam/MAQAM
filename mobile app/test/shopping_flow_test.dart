import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/cart/domain/cart.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

/// Opens the first product from the home screen's new arrivals.
Future<void> _openProduct(WidgetTester tester) async {
  await tester.tap(find.text('مفتاح تجريبي').first);
  await tester.pumpAndSettle();
}

Future<void> _addToCartAndOpenIt(WidgetTester tester) async {
  await _openProduct(tester);
  await tester.tap(find.textContaining('أضف إلى السلة'));
  await tester.pump();
  // Let the confirmation slide in before tapping its action.
  await tester.pump(const Duration(seconds: 1));
  await tester.tap(find.text('عرض السلة'));
  await tester.pumpAndSettle();
}

Future<void> _tapVisible(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('a product shows its variants, and the price follows the chosen one', (tester) async {
    await pumpApp(tester, session: _saved);
    await _openProduct(tester);

    expect(find.text('متوفر'), findsOneWidget);
    expect(find.text('75.00 ج.م'), findsWidgets);
    expect(find.text('باقي 3 فقط'), findsOneWidget);
    expect(find.text('وصف تجريبي للمنتج.'), findsOneWidget);
    expect(find.text('أضف إلى السلة · 75.00 ج.م'), findsOneWidget);

    // The specifications list the code, weight and size the admin entered.
    expect(find.text('MQM-TEST-01'), findsOneWidget);
    expect(find.text('0.25 كجم'), findsOneWidget);
    expect(find.text('86 x 86 mm'), findsOneWidget);

    // Choosing a dearer variant and a second unit updates the price and the total.
    await tester.tap(find.text('60 مللي'));
    await tester.pumpAndSettle();
    expect(find.text('90.00 ج.م'), findsOneWidget);
    await tester.tap(find.byTooltip('زيادة الكمية'));
    await tester.pump();
    expect(find.text('أضف إلى السلة · 180.00 ج.م'), findsOneWidget);

    // No more than what is in stock.
    await tester.tap(find.byTooltip('زيادة الكمية'));
    await tester.tap(find.byTooltip('زيادة الكمية'));
    await tester.pump();
    expect(find.text('أضف إلى السلة · 270.00 ج.م'), findsOneWidget);
  });

  testWidgets('adding to the cart sends the chosen quantity, variant and colour', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openProduct(tester);

    await tester.tap(find.byTooltip('زيادة الكمية'));
    await tester.tap(find.text('60 مللي'));
    await tester.tap(find.bySemanticsLabel('أبيض'));
    await tester.pump();
    await tester.tap(find.textContaining('أضف إلى السلة'));
    await tester.pump();

    expect(app.cart.added.single, (productId: 1, quantity: 2, optionId: 12, color: 'أبيض'));
    expect(find.text('تمت إضافة المنتج إلى السلة.'), findsOneWidget);
  });

  testWidgets('a guest is asked to sign in before adding to the cart', (tester) async {
    final app = await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();
    await _openProduct(tester);

    await tester.tap(find.textContaining('أضف إلى السلة'));
    await tester.pump();

    expect(app.cart.added, isEmpty);
    expect(find.text('سجّل الدخول لإضافة المنتجات إلى سلتك.'), findsOneWidget);
  });

  testWidgets('the cart changes quantities, removes items and applies a code', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _addToCartAndOpenIt(tester);

    expect(find.text('السلة · منتج واحد'), findsOneWidget);
    expect(find.text('110.00 ج.م'), findsOneWidget, reason: '75 + 35 shipping');

    await tester.tap(find.byTooltip('زيادة الكمية'));
    await tester.pumpAndSettle();
    expect(find.text('السلة · منتجان'), findsOneWidget);
    expect(find.text('185.00 ج.م'), findsOneWidget);

    await tester.enterText(find.byType(TextField), 'WRONG');
    await tester.tap(find.text('تطبيق'));
    await tester.pumpAndSettle();
    expect(find.text('الكود غير صالح.'), findsOneWidget);
    expect(app.cart.coupon, isNull);

    await tester.enterText(find.byType(TextField), 'SAVE10');
    await tester.tap(find.text('تطبيق'));
    await tester.pumpAndSettle();
    expect(find.text('تم تطبيق الكود.'), findsOneWidget);
    expect(find.text('- 10.00 ج.م'), findsOneWidget);
    expect(find.text('175.00 ج.م'), findsOneWidget);

    await tester.tap(find.text('إزالة'));
    await tester.pumpAndSettle();
    expect(find.text('185.00 ج.م'), findsOneWidget);

    // Two taps on minus: down to one, then out of the cart.
    await tester.tap(find.byTooltip('تقليل الكمية'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('تقليل الكمية'));
    await tester.pumpAndSettle();
    expect(find.text('سلتك فارغة'), findsOneWidget);
  });

  testWidgets('checkout with a new address validates it, then places a cash order', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _addToCartAndOpenIt(tester);
    await tester.tap(find.text('متابعة لإتمام الطلب'));
    await tester.pumpAndSettle();

    // No saved address: the form is shown, started from the account's name and phone.
    expect(find.text('اسم المستلم'), findsOneWidget);
    expect(find.widgetWithText(TextFormField, 'أحمد علي'), findsOneWidget);
    expect(find.text('رصيدك 400 نقطة'), findsOneWidget);

    await tester.tap(find.text('تأكيد الطلب'));
    await tester.pumpAndSettle();
    expect(find.text('هذا الحقل مطلوب.'), findsNWidgets(3));
    expect(app.cart.placed, isNull);

    final fields = find.byType(TextFormField);
    await tester.enterText(fields.at(2), 'القاهرة');
    await tester.enterText(fields.at(3), 'مدينة نصر');
    await tester.enterText(fields.at(4), 'شارع الطيران عمارة 12');
    await tester.pump();
    await tester.tap(find.text('تأكيد الطلب'));
    await tester.pumpAndSettle();

    final placed = app.cart.placed!;
    expect(placed.payment, PaymentMethod.cashOnDelivery);
    expect(placed.addressId, isNull);
    expect(placed.newAddress!.recipientName, 'أحمد علي');
    expect(placed.newAddress!.city, 'مدينة نصر');

    expect(find.text('تم استلام طلبك'), findsOneWidget);
    expect(find.text('MQ-TEST-0001'), findsOneWidget);

    await tester.tap(find.text('العودة للرئيسية'));
    await tester.pumpAndSettle();
    expect(find.text('وصل حديثاً'), findsOneWidget);
  });

  testWidgets('checkout uses the saved address and the chosen payment method', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.cart.savedAddresses.add(const Address(
      id: 5,
      recipientName: 'أحمد محمود',
      phone: '01012345678',
      governorate: 'القاهرة',
      city: 'مدينة نصر',
      street: 'شارع الطيران',
      isDefault: true,
    ));
    await _addToCartAndOpenIt(tester);
    await tester.tap(find.text('متابعة لإتمام الطلب'));
    await tester.pumpAndSettle();

    expect(find.text('القاهرة، مدينة نصر، شارع الطيران'), findsOneWidget);
    expect(find.text('اسم المستلم'), findsNothing);

    await _tapVisible(tester, find.text('نقاط الولاء'));
    await tester.enterText(find.byType(TextField), 'اتصل قبل الوصول');
    await tester.tap(find.text('تأكيد الطلب'));
    await tester.pumpAndSettle();

    final placed = app.cart.placed!;
    expect(placed.addressId, 5);
    expect(placed.newAddress, isNull);
    expect(placed.payment, PaymentMethod.loyaltyPoints);
    expect(placed.notes, 'اتصل قبل الوصول');
    expect(find.text('تم استلام طلبك'), findsOneWidget);
  });

  testWidgets('similar products are offered and open their own page', (tester) async {
    await pumpApp(tester, session: _saved);
    await _openProduct(tester);

    await tester.ensureVisible(find.text('منتجات مشابهة'));
    await tester.pumpAndSettle();
    // The product being viewed is not listed as similar to itself.
    await tester.tap(find.text('بدون سعر'));
    await tester.pumpAndSettle();

    expect(find.widgetWithText(FilledButton, 'غير متوفر'), findsOneWidget);
  });

  Future<void> payOnline(WidgetTester tester, TestApp app) async {
    app.cart.paymentUrl = Uri.parse('https://pay.example.test/s/1');
    app.cart.savedAddresses.add(const Address(
      id: 5,
      recipientName: 'أحمد محمود',
      phone: '01012345678',
      governorate: 'القاهرة',
      city: 'مدينة نصر',
      street: 'شارع الطيران',
      isDefault: true,
    ));
    await _addToCartAndOpenIt(tester);
    await tester.tap(find.text('متابعة لإتمام الطلب'));
    await tester.pumpAndSettle();
    await _tapVisible(tester, find.text('بطاقة أو محفظة إلكترونية'));
    await tester.tap(find.text('تأكيد الطلب'));
    await tester.pumpAndSettle();
  }

  testWidgets('online payment opens inside the app and confirms once paid', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await payOnline(tester, app);

    expect(app.cart.placed!.payment, PaymentMethod.online);
    expect(find.text('دفع آمن'), findsOneWidget);
    expect(find.text('gateway page https://pay.example.test/s/1'), findsOneWidget);

    await tester.tap(find.textContaining('gateway page'));
    await tester.pumpAndSettle();

    expect(find.text('تم الدفع واستلام طلبك'), findsOneWidget);
    expect(find.text('MQ-TEST-0001'), findsOneWidget);
  });

  testWidgets('a declined payment is reported as such', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.cart.paymentOutcome = PaymentState.failed;
    await payOnline(tester, app);

    await tester.tap(find.textContaining('gateway page'));
    await tester.pumpAndSettle();

    expect(find.text('لم يكتمل الدفع'), findsOneWidget);
    expect(find.text('لا تنسَ نقاطك'), findsNothing);
  });

  testWidgets('closing the payment page asks first, then reports the payment as unconfirmed',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.cart.paymentOutcome = PaymentState.pending;
    await payOnline(tester, app);

    await tester.tap(find.byTooltip('إغلاق'));
    await tester.pumpAndSettle();
    expect(find.text('إغلاق صفحة الدفع؟'), findsOneWidget);

    await tester.tap(find.text('متابعة الدفع'));
    await tester.pumpAndSettle();
    expect(find.textContaining('gateway page'), findsOneWidget);

    await tester.tap(find.byTooltip('إغلاق'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(TextButton, 'إغلاق'));
    await tester.pumpAndSettle();

    expect(find.text('تم استلام طلبك'), findsOneWidget);
    expect(find.textContaining('لم نتأكد من الدفع بعد'), findsOneWidget);
  });

  testWidgets('an out-of-stock product cannot be added', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await tester.tap(find.text('بدون سعر').first);
    await tester.pumpAndSettle();

    expect(find.widgetWithText(FilledButton, 'غير متوفر'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'غير متوفر'));
    await tester.pump();
    expect(app.cart.added, isEmpty);
  });
}
