import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/test_app.dart';

const _phone = '01012345678';

Future<void> _enterPhone(WidgetTester tester) async {
  await openLogin(tester);
  await tester.enterText(find.byType(TextField), '1012345678');
  await tester.tap(find.text('متابعة'));
  // Not pumpAndSettle: the "waiting" spinner on the next screen never settles,
  // and settling would run the clock straight through the WhatsApp polling.
  await tester.pump();
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 500));
}

/// From the "send the code" screen through to a verified sign-in.
Future<void> _passWhatsapp(WidgetTester tester) async {
  await tester.pump(const Duration(seconds: 4));
  await tester.pump(const Duration(seconds: 4));
  await tester.pumpAndSettle();
  await tester.enterText(find.byType(TextField), '123456');
  await tester.pumpAndSettle();
}

/// The text fields of the current screen, top to bottom.
Finder _field(int index) => find.byType(TextField).at(index);

void main() {
  testWidgets('a first-time customer chooses a name and password after the WhatsApp code',
      (tester) async {
    final app = await pumpApp(tester);
    app.auth.whatsappAccountHasPassword = false;

    await _enterPhone(tester);
    // No password on this number yet, so it goes to WhatsApp.
    expect(find.text('أرسل الكود من واتساب'), findsOneWidget);
    await _passWhatsapp(tester);

    expect(find.text('أكمل بياناتك'), findsOneWidget);

    await tester.tap(find.text('حفظ ومتابعة'));
    await tester.pump();
    expect(find.text('أدخل اسمك.'), findsOneWidget);

    await tester.enterText(_field(0), 'أحمد محمود');
    await tester.enterText(_field(1), 'short');
    await tester.tap(find.text('حفظ ومتابعة'));
    await tester.pump();
    expect(find.text('كلمة المرور يجب ألا تقل عن 8 خانات.'), findsOneWidget);

    await tester.enterText(_field(1), 'pass-1234');
    await tester.enterText(_field(2), 'pass-9999');
    await tester.tap(find.text('حفظ ومتابعة'));
    await tester.pump();
    expect(find.text('كلمتا المرور غير متطابقتين.'), findsOneWidget);
    expect(app.auth.savedPassword, isNull);

    await tester.enterText(_field(2), 'pass-1234');
    await tester.enterText(_field(3), 'مدينة نصر');
    await tester.tap(find.text('حفظ ومتابعة'));
    await tester.pumpAndSettle();

    expect(app.auth.completedName, 'أحمد محمود');
    expect(app.auth.savedPassword, 'pass-1234');
    // The app now greets them by the name they just gave.
    expect(find.text('أهلاً أحمد · رصيد نقاطك'), findsOneWidget);
    expect(app.store.values['session'], contains('أحمد محمود'));
  });

  testWidgets('completing details can be put off', (tester) async {
    final app = await pumpApp(tester);
    app.auth.whatsappAccountHasPassword = false;

    await _enterPhone(tester);
    await _passWhatsapp(tester);
    await tester.tap(find.text('لاحقاً'));
    await tester.pumpAndSettle();

    expect(app.auth.savedPassword, isNull);
    expect(find.text('وصل حديثاً'), findsOneWidget);
  });

  testWidgets('a returning customer signs in with their password', (tester) async {
    final app = await pumpApp(tester);
    app.auth.passwordAccounts[_phone] = 'pass-1234';

    await _enterPhone(tester);
    expect(find.text('أدخل كلمة المرور'), findsOneWidget);
    expect(app.auth.startedPhone, isNull, reason: 'no WhatsApp challenge is started');

    await tester.tap(find.text('دخول'));
    await tester.pump();
    expect(find.text('أدخل كلمة المرور.'), findsOneWidget);

    await tester.enterText(_field(0), 'wrong-one');
    await tester.tap(find.text('دخول'));
    await tester.pumpAndSettle();
    expect(find.text('رقم الجوال أو كلمة المرور غير صحيحة.'), findsOneWidget);

    await tester.enterText(_field(0), 'pass-1234');
    await tester.tap(find.text('دخول'));
    await tester.pumpAndSettle();

    expect(find.text('أهلاً أحمد · رصيد نقاطك'), findsOneWidget);
    expect(app.store.values['session'], contains('password-token'));
  });

  testWidgets('a forgotten password is replaced after a WhatsApp code', (tester) async {
    final app = await pumpApp(tester);
    app.auth.passwordAccounts[_phone] = 'forgotten';

    await _enterPhone(tester);
    await tester.tap(find.text('نسيت كلمة المرور؟ ادخل بكود واتساب'));
    await tester.pump();
    await tester.pump();
    expect(app.auth.startedPhone, _phone);
    await _passWhatsapp(tester);

    // Only the password is asked for; the name is already known.
    expect(find.text('كلمة مرور جديدة'), findsOneWidget);
    expect(find.text('الاسم الكامل'), findsNothing);
    expect(find.byType(TextField), findsNWidgets(2));

    await tester.enterText(_field(0), 'brand-new-1');
    await tester.enterText(_field(1), 'brand-new-1');
    await tester.tap(find.text('حفظ ومتابعة'));
    await tester.pumpAndSettle();

    expect(app.auth.savedPassword, 'brand-new-1');
    expect(find.text('وصل حديثاً'), findsOneWidget);
  });

  testWidgets('password sign-in is followed by the biometric offer on a capable device',
      (tester) async {
    final app = await pumpApp(tester, biometricsAvailable: true);
    app.auth.passwordAccounts[_phone] = 'pass-1234';

    await _enterPhone(tester);
    await tester.enterText(_field(0), 'pass-1234');
    await tester.tap(find.text('دخول'));
    await tester.pumpAndSettle();

    expect(find.text('ادخل أسرع المرة القادمة'), findsOneWidget);
  });
}
