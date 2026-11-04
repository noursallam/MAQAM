import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/security/app_lock.dart';
import 'package:maqam_app/core/security/biometric_authenticator.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/core/session/quick_sign_in.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');
const _remembered = QuickSignInCredential(secret: 'quick-secret-1', phone: '01012345678', name: 'أحمد علي');

Future<void> _signOut(WidgetTester tester) async {
  await openTab(tester, 'حسابي');
  await tester.ensureVisible(find.text('تسجيل الخروج'));
  await tester.tap(find.text('تسجيل الخروج'));
  await tester.pumpAndSettle();
  await tester.tap(find.widgetWithText(TextButton, 'تسجيل الخروج').last);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('turning the lock on registers the phone for quick sign-in', (tester) async {
    final app = await pumpApp(tester, session: _saved, biometricsAvailable: true);
    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('قفل التطبيق'));
    await tester.tap(find.text('قفل التطبيق'));
    await tester.pumpAndSettle();

    expect(app.store.values['biometric_lock'], 'enabled');
    expect(app.store.values['quick_sign_in'], contains('quick-secret-1'));
    expect(app.auth.quickSecrets, {'quick-secret-1'});
  });

  testWidgets('after signing out, the fingerprint signs back in without typing anything', (tester) async {
    final app = await pumpApp(
      tester,
      session: _saved,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
    );
    // The lock asks first at launch.
    await tester.pumpAndSettle();
    await _signOut(tester);

    expect(app.auth.signedOut, isTrue);
    expect(find.text('أهلاً بعودتك، أحمد'), findsOneWidget);
    expect(find.byTooltip('الدخول ببصمة الإصبع'), findsOneWidget);
    // Nothing to type: no phone or password field.
    expect(find.byType(TextField), findsNothing);

    await tester.tap(find.byTooltip('الدخول ببصمة الإصبع'));
    await tester.pumpAndSettle();

    expect(app.auth.quickSignIns, greaterThanOrEqualTo(1));
    expect(app.store.values['session'], contains('quick-token'));
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a phone that remembers its customer opens on the touch screen, not the login form',
      (tester) async {
    final app = await pumpApp(
      tester,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
      quickSignIn: _remembered,
      acceptedQuickSecrets: {'quick-secret-1'},
    );

    // The prompt opens by itself and the fake confirms it.
    await tester.pumpAndSettle();

    expect(app.auth.quickSignIns, 1);
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a phone locked with the app PIN signs back in with that PIN', (tester) async {
    final app = await pumpApp(
      tester,
      biometricPreference: BiometricPreference.pin,
      quickSignIn: _remembered,
      acceptedQuickSecrets: {'quick-secret-1'},
    );
    // The PIN chosen earlier, saved the way the app saves it.
    await ProviderScope.containerOf(tester.element(find.byType(MaterialApp)))
        .read(appLockProvider.notifier)
        .setPin('2468');
    await tester.pumpAndSettle();

    await typePin(tester, '2468');

    expect(app.auth.quickSignIns, 1);
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a secret the server no longer accepts falls back to the login form', (tester) async {
    final app = await pumpApp(
      tester,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
      quickSignIn: _remembered,
    );
    // Never added to the server's accepted secrets: signed out everywhere, say.
    await tester.pumpAndSettle();

    expect(app.auth.quickSignIns, 0);
    expect(find.text('لم يعد الدخول السريع متاحاً على هذا الهاتف.'), findsOneWidget);
    expect(find.text('رقم الجوال'), findsOneWidget);
    expect(app.store.values.containsKey('quick_sign_in'), isFalse);
  });

  testWidgets('"not your account" forgets the phone and goes to the login form', (tester) async {
    final app = await pumpApp(
      tester,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
      biometricAnswers: const [false],
      quickSignIn: _remembered,
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('ليس حسابك؟ دخول برقم آخر'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الجوال'), findsOneWidget);
    expect(app.store.values.containsKey('quick_sign_in'), isFalse);
    expect(app.store.values.containsKey('biometric_lock'), isFalse);
  });

  testWidgets('a guest can still browse from the sign-in-again screen', (tester) async {
    await pumpApp(
      tester,
      biometricsAvailable: true,
      biometricPreference: BiometricPreference.enabled,
      biometricAnswers: const [false],
      quickSignIn: _remembered,
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('تصفّح كزائر'));
    await tester.pumpAndSettle();

    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('an iPhone with Face ID is offered Face ID by name', (tester) async {
    debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
    addTearDown(() => debugDefaultTargetPlatformOverride = null);

    await pumpApp(
      tester,
      biometricsAvailable: true,
      sensor: BiometricKind.face,
      biometricPreference: BiometricPreference.enabled,
      biometricAnswers: const [false],
      quickSignIn: _remembered,
    );
    await tester.pumpAndSettle();

    expect(find.byTooltip('الدخول بـ Face ID'), findsOneWidget);
    debugDefaultTargetPlatformOverride = null;
  });
}
