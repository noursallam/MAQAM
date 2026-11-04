import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/app/app.dart';
import 'package:maqam_app/core/security/app_lock.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

/// Goes from onboarding through the WhatsApp code to a signed-in session.
Future<void> _signIn(WidgetTester tester) async {
  await openLogin(tester);
  await tester.enterText(find.byType(TextField), '1012345678');
  await tester.tap(find.text('متابعة'));
  await tester.pump();
  await _receiveAndEnterOtp(tester);
}

Future<void> _receiveAndEnterOtp(WidgetTester tester) async {
  await tester.pump();
  await tester.pump(const Duration(seconds: 4));
  await tester.pump(const Duration(seconds: 4));
  await tester.pumpAndSettle();
  await tester.enterText(find.byType(TextField), '123456');
  await tester.pumpAndSettle();
}

Future<TestApp> _pumpLocked(WidgetTester tester, {List<bool> answers = const [false]}) {
  return pumpApp(
    tester,
    session: _saved,
    biometricsAvailable: true,
    biometricPreference: BiometricPreference.enabled,
    biometricAnswers: answers,
  );
}

void main() {
  testWidgets('after signing in, the customer can enable biometric unlock', (tester) async {
    final app = await pumpApp(tester, biometricsAvailable: true);
    await _signIn(tester);

    expect(find.text('ادخل أسرع المرة القادمة'), findsOneWidget);

    await tester.tap(find.text('تفعيل الدخول ببصمة الإصبع'));
    await tester.pumpAndSettle();

    expect(app.biometrics.reasons, hasLength(1));
    expect(app.store.values['biometric_lock'], 'enabled');
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('declining is remembered and goes on to the store', (tester) async {
    final app = await pumpApp(tester, biometricsAvailable: true);
    await _signIn(tester);

    // Below the fold on the small test surface.
    await tester.ensureVisible(find.text('ليس الآن'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('ليس الآن'));
    await tester.pumpAndSettle();

    expect(app.biometrics.reasons, isEmpty);
    expect(app.store.values['biometric_lock'], 'declined');
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a failed confirmation does not enable the lock', (tester) async {
    final app = await pumpApp(tester, biometricsAvailable: true, biometricAnswers: [false]);
    await _signIn(tester);

    await tester.tap(find.text('تفعيل الدخول ببصمة الإصبع'));
    await tester.pumpAndSettle();

    expect(find.textContaining('لم نتمكن من التحقق'), findsOneWidget);
    expect(app.store.values.containsKey('biometric_lock'), isFalse);
    expect(find.text('مفتاح تجريبي'), findsNothing);
  });

  testWidgets('a device without a sensor is offered an app PIN, which then locks the app',
      (tester) async {
    final app = await pumpApp(tester, biometricPreference: BiometricPreference.undecided);
    await _signIn(tester);

    expect(find.text('احمِ تطبيقك برمز PIN'), findsOneWidget);
    expect(find.text('تفعيل الدخول ببصمة الإصبع'), findsNothing);

    await tester.tap(find.text('إنشاء رمز PIN'));
    await tester.pumpAndSettle();
    expect(find.text('أنشئ رمز PIN'), findsOneWidget);

    // A slip on the second entry starts the choice over.
    await typePin(tester, '1234');
    expect(find.text('أعد إدخال الرمز'), findsOneWidget);
    await typePin(tester, '1235');
    expect(find.text('الرمزان غير متطابقين. ابدأ من جديد.'), findsOneWidget);
    expect(app.store.values.containsKey('app_pin'), isFalse);

    await typePin(tester, '1234');
    await typePin(tester, '1234');

    expect(find.text('مفتاح تجريبي'), findsOneWidget);
    expect(app.store.values['biometric_lock'], 'pin');
    // Only a salted hash is stored, never the digits.
    expect(app.store.values['app_pin'], isNot(contains('1234')));

    // Next time the app comes back from the background it asks for the PIN.
    final container = ProviderScope.containerOf(tester.element(find.byType(MaqamApp)));
    container.read(appLockProvider.notifier).lock();
    await tester.pumpAndSettle();
    expect(find.text('أدخل رمز PIN'), findsOneWidget);

    await typePin(tester, '0000');
    expect(find.text('الرمز غير صحيح. المحاولات المتبقية: 4'), findsOneWidget);
    expect(find.text('مفتاح تجريبي'), findsNothing);

    await typePin(tester, '1234');
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('the PIN offer can be skipped', (tester) async {
    final app = await pumpApp(tester, biometricPreference: BiometricPreference.undecided);
    await _signIn(tester);

    await tester.tap(find.text('تخطي'));
    await tester.pumpAndSettle();

    expect(app.store.values['biometric_lock'], 'declined');
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('five wrong PINs block it until a WhatsApp sign-in', (tester) async {
    final app = await pumpApp(tester, biometricPreference: BiometricPreference.undecided);
    await _signIn(tester);
    await tester.tap(find.text('إنشاء رمز PIN'));
    await tester.pumpAndSettle();
    await typePin(tester, '1234');
    await typePin(tester, '1234');

    final container = ProviderScope.containerOf(tester.element(find.byType(MaqamApp)));
    container.read(appLockProvider.notifier).lock();
    await tester.pumpAndSettle();

    for (var i = 0; i < 5; i++) {
      await typePin(tester, '0000');
    }
    expect(find.textContaining('تم إيقاف الرمز'), findsOneWidget);
    // The pad is gone; even the right PIN can no longer be typed.
    expect(find.widgetWithText(InkWell, '1'), findsNothing);
    expect(app.store.values['app_pin_failures'], '5');

    await tester.tap(find.text('الدخول بكود واتساب'));
    await tester.pump();
    await tester.pump();
    await _receiveAndEnterOtp(tester);

    expect(find.text('مفتاح تجريبي'), findsOneWidget);
    expect(app.store.values.containsKey('app_pin_failures'), isFalse);
  });

  testWidgets('a device with a sensor can still choose a PIN', (tester) async {
    final app = await pumpApp(tester, biometricsAvailable: true);
    await _signIn(tester);

    await tester.tap(find.text('استخدام رمز PIN بدلاً من ذلك'));
    await tester.pumpAndSettle();
    await typePin(tester, '4321');
    await typePin(tester, '4321');

    expect(app.biometrics.reasons, isEmpty);
    expect(app.store.values['biometric_lock'], 'pin');
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a locked app prompts on launch and opens once confirmed', (tester) async {
    final app = await _pumpLocked(tester, answers: [false, true]);

    // The automatic prompt was refused: still locked, nothing from the store shown.
    expect(find.text('أهلاً بعودتك، أحمد'), findsOneWidget);
    expect(find.text('010 •••• 5678'), findsOneWidget);
    expect(find.text('لم نتمكن من التحقق. حاول مرة أخرى.'), findsOneWidget);
    expect(find.text('مفتاح تجريبي'), findsNothing);

    await tester.tap(find.byTooltip('الدخول ببصمة الإصبع'));
    await tester.pumpAndSettle();

    expect(app.biometrics.reasons, hasLength(2));
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a locked app can be opened with a WhatsApp code instead', (tester) async {
    final app = await _pumpLocked(tester);

    await tester.tap(find.text('الدخول بكود واتساب'));
    await tester.pump();
    await tester.pump();
    expect(app.auth.startedPhone, '01012345678');

    await _receiveAndEnterOtp(tester);

    // Already enabled, so no second offer; the new token replaced the old one.
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
    expect(app.store.values['session'], contains('token-1'));
    expect(app.store.values.containsKey('biometric_lock'), isFalse,
        reason: 'the in-memory preference came from the override, nothing rewrote it');
  });

  testWidgets('"not your account" signs out and asks for a phone number', (tester) async {
    final app = await _pumpLocked(tester);
    await app.store.write('biometric_lock', 'enabled');

    await tester.tap(find.text('ليس حسابك؟ دخول برقم آخر'));
    await tester.pumpAndSettle();

    expect(app.auth.signedOut, isTrue);
    expect(app.store.values.containsKey('biometric_lock'), isFalse);
    expect(find.text('تسجيل الدخول'), findsOneWidget);
    expect(find.text('رقم الجوال'), findsOneWidget);
  });

  testWidgets('re-locking covers the store until the owner is confirmed again', (tester) async {
    await _pumpLocked(tester, answers: [true, false]);
    expect(find.text('مفتاح تجريبي'), findsOneWidget);

    final container = ProviderScope.containerOf(tester.element(find.byType(MaqamApp)));
    container.read(appLockProvider.notifier).lock();
    await tester.pumpAndSettle();

    expect(find.text('أهلاً بعودتك، أحمد'), findsOneWidget);
    expect(find.text('مفتاح تجريبي'), findsNothing);
  });

  testWidgets('a guest is never locked out', (tester) async {
    await pumpApp(
      tester,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
    );

    expect(find.text('الفخامة في كل لمسة'), findsOneWidget);
  });
}
