import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

void main() {
  testWidgets('phone entry validates the number before continuing', (tester) async {
    final app = await pumpApp(tester);
    expect(find.text('الفخامة في كل لمسة'), findsOneWidget);
    await openLogin(tester);

    await tester.tap(find.text('متابعة'));
    await tester.pump();
    expect(find.text('أدخل رقم الجوال.'), findsOneWidget);

    await tester.enterText(find.byType(TextField), '123');
    await tester.tap(find.text('متابعة'));
    await tester.pump();
    expect(find.textContaining('رقم الجوال غير صحيح'), findsOneWidget);
    expect(app.auth.startedPhone, isNull);
  });

  testWidgets('a customer signs in with a WhatsApp code and can sign out', (tester) async {
    final app = await pumpApp(tester);
    await openLogin(tester);

    await tester.enterText(find.byType(TextField), '1012345678');
    await tester.tap(find.text('متابعة'));
    await tester.pump();
    await tester.pump();

    expect(app.auth.startedPhone, '01012345678');
    expect(find.text('MAQAM-482913'), findsOneWidget);
    expect(find.text('في انتظار رسالتك...'), findsOneWidget);

    // First poll: nothing yet. Second poll: the sign-in code has been sent.
    await tester.pump(const Duration(seconds: 4));
    expect(find.text('MAQAM-482913'), findsOneWidget);
    await tester.pump(const Duration(seconds: 4));
    await tester.pumpAndSettle();
    expect(find.text('أدخل كود الدخول'), findsOneWidget);

    await tester.enterText(find.byType(TextField), '000000');
    await tester.pump();
    await tester.pump();
    expect(find.text('كود الدخول غير صحيح.'), findsOneWidget);

    await tester.enterText(find.byType(TextField), '123456');
    await tester.pumpAndSettle();
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
    expect(app.store.values['session'], contains('token-1'));

    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();
    // Confirm in the dialog.
    await tester.tap(find.widgetWithText(TextButton, 'تسجيل الخروج').last);
    await tester.pumpAndSettle();
    expect(app.auth.signedOut, isTrue);
    expect(app.store.values.containsKey('session'), isFalse);
    expect(find.text('الفخامة في كل لمسة'), findsOneWidget);
  });

  testWidgets('a saved session opens the store directly', (tester) async {
    await pumpApp(tester, session: const AuthSession(token: 'saved', fullName: 'أحمد علي'));

    expect(find.text('مفتاح تجريبي'), findsOneWidget);
    expect(find.text('أهلاً أحمد · رصيد نقاطك'), findsOneWidget);
    expect(find.textContaining('400'), findsOneWidget);
    expect(find.text('باقي 550 نقطة للوصول إلى رتبة ذهبي'), findsOneWidget);
  });

  testWidgets('sign-in can go back and switch language', (tester) async {
    await pumpApp(tester);
    await openLogin(tester);

    await tester.tap(find.text('متابعة'));
    await tester.pump();

    await tester.tap(find.text('English'));
    await tester.pumpAndSettle();
    expect(find.text('Sign in'), findsOneWidget);
    expect(find.text('Enter your mobile number.'), findsOneWidget);
    expect(Directionality.of(tester.element(find.text('Sign in'))), TextDirection.ltr);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.text('Prestige in Every Touch'), findsOneWidget);
  });

  testWidgets('a guest can browse the store and filter by category', (tester) async {
    final app = await pumpApp(tester);

    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    // A guest sees an invitation instead of a points balance.
    expect(find.text('اكسب نقاطاً مع كل عبوة'), findsOneWidget);

    await openTab(tester, 'المتجر');
    expect(find.text('113 منتج'), findsOneWidget);
    expect(find.text('75.00 ج.م'), findsOneWidget);
    expect(find.text('السعر عند الطلب'), findsOneWidget);
    expect(find.text('غير متوفر'), findsOneWidget);

    await tester.tap(find.text('مفاتيح'));
    await tester.pumpAndSettle();
    expect(app.catalog.requestedCategoryIds.last, 7);
  });

  testWidgets('the store shows a retry state when loading fails', (tester) async {
    await pumpApp(tester, failCatalog: true);

    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    expect(find.text('تعذر تحميل المنتجات'), findsOneWidget);
    expect(find.text('إعادة المحاولة'), findsOneWidget);
  });

  testWidgets('onboarding scrolls instead of overflowing on a short screen with large text',
      (tester) async {
    tester.platformDispatcher.textScaleFactorTestValue = 2;
    addTearDown(tester.platformDispatcher.clearAllTestValues);

    await pumpApp(tester, surfaceSize: const Size(320, 480));

    expect(tester.takeException(), isNull);
  });
}
