import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/security/app_lock.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/loyalty/domain/wallet.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

void main() {
  testWidgets('the store searches after a pause in typing', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'المتجر');

    await tester.enterText(find.byType(TextField), 'مفتاح');
    await tester.pump(const Duration(milliseconds: 200));
    expect(app.catalog.requestedSearches, isNot(contains('مفتاح')));

    await tester.pump(const Duration(milliseconds: 300));
    await tester.pumpAndSettle();
    expect(app.catalog.requestedSearches.last, 'مفتاح');
  });

  testWidgets('the wallet shows balances and filters the activity', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'المحفظة');

    expect(find.text('متاح للصرف'), findsOneWidget);
    expect(find.text('400'), findsOneWidget);
    expect(find.text('450'), findsOneWidget);
    expect(find.text('ذهبي عند 1000'), findsOneWidget);
    expect(find.text('+25'), findsOneWidget);
    expect(find.text('-50'), findsOneWidget);
    expect(find.text('اليوم'), findsOneWidget);

    await tester.ensureVisible(find.text('صرف'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('صرف'));
    await tester.pumpAndSettle();
    expect(app.loyalty.requestedFilters.last, TransactionFilter.spent);
  });

  testWidgets('a guest is asked to sign in on the wallet and account tabs', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await openTab(tester, 'المحفظة');
    expect(find.text('سجّل الدخول لعرض نقاطك وحركاتك.'), findsOneWidget);

    await openTab(tester, 'حسابي');
    expect(find.text('سجّل الدخول لإدارة حسابك وطلباتك.'), findsOneWidget);

    await tester.tap(find.widgetWithText(FilledButton, 'تسجيل الدخول'));
    await tester.pumpAndSettle();
    expect(find.text('رقم الجوال'), findsOneWidget);
  });

  testWidgets('the account tab shows the profile and switches language', (tester) async {
    await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');

    expect(find.text('أحمد علي'), findsOneWidget);
    expect(find.text('010 1234 5678'), findsOneWidget);

    await tester.ensureVisible(find.text('اللغة'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('اللغة'));
    await tester.pumpAndSettle();

    expect(find.text('Language'), findsOneWidget);
    expect(find.text('Account'), findsWidgets);
  });

  testWidgets('the app lock can be turned on and off from the account tab', (tester) async {
    final app = await pumpApp(tester, session: _saved, biometricsAvailable: true);
    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();
    expect(find.text('غير مفعّل'), findsOneWidget);

    await tester.tap(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();
    expect(app.biometrics.reasons, hasLength(1));
    expect(find.text('بصمة أو وجه'), findsOneWidget);
    expect(app.store.values['biometric_lock'], BiometricPreference.enabled.name);

    await tester.tap(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();
    expect(find.text('غير مفعّل'), findsOneWidget);
    expect(app.store.values['biometric_lock'], BiometricPreference.declined.name);
  });

  testWidgets('without a sensor, the account tab sets up a PIN and comes back', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();
    await typePin(tester, '2468');
    await typePin(tester, '2468');

    expect(find.text('رمز PIN'), findsOneWidget);
    expect(app.store.values['biometric_lock'], BiometricPreference.pin.name);

    // Turning it off forgets the PIN as well.
    await tester.tap(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();
    expect(app.store.values.containsKey('app_pin'), isFalse);
  });

  testWidgets('every home shortcut opens its screen', (tester) async {
    await pumpApp(tester, session: _saved);

    await tester.tap(find.text('عجلة الحظ'));
    await tester.pumpAndSettle();
    expect(find.text('لفّ العجلة'), findsOneWidget);
    await tester.tap(find.byTooltip('رجوع'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('مكافآتي'));
    await tester.pumpAndSettle();
    expect(find.text('جرّب حظك في العجلة'), findsOneWidget);
    await tester.tap(find.byTooltip('رجوع'));
    await tester.pumpAndSettle();

    await tester.tap(find.byTooltip('المساعد'));
    await tester.pumpAndSettle();
    expect(find.text('مساعد MAQAM'), findsOneWidget);
  });

  testWidgets('a guest who taps the wheel is asked to sign in', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('عجلة الحظ'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الجوال'), findsOneWidget);
  });
}
