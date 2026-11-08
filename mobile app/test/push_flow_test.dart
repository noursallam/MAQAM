import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/push/push_messaging.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

void main() {
  testWidgets('a signed-in device registers for push, and signing out unregisters it',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);

    expect(app.notifications.devices.single.token, 'push-token-1');

    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('تسجيل الخروج').last);
    await tester.pumpAndSettle();

    // The server is told which device to stop pushing to.
    expect(app.auth.signedOutPushToken, 'push-token-1');
  });

  testWidgets('a guest device registers nothing until it signs in', (tester) async {
    final app = await pumpApp(tester);

    expect(app.notifications.devices, isEmpty);
  });

  testWidgets('a push that arrives while the app is open shows a banner that opens the order',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);

    app.push.foreground.add(const PushMessage(
      title: 'تحديث الطلب MQ-ACTIVE-1',
      body: 'تم شحن طلبك وهو في الطريق إليك.',
      orderNumber: 'MQ-ACTIVE-1',
    ));
    // Deliver the message, build the banner, then let it finish sliding in.
    await tester.pump();
    await tester.pump();
    await tester.pump(const Duration(seconds: 1));

    expect(find.textContaining('تم شحن طلبك'), findsOneWidget);

    await tester.tap(find.text('عرض'));
    await tester.pumpAndSettle();
    expect(find.text('تفاصيل الطلب'), findsOneWidget);
  });

  testWidgets('tapping a push from the background opens what it is about', (tester) async {
    final app = await pumpApp(tester, session: _saved);

    // About a rank, not an order: the notifications list is the place for it.
    app.push.opened.add(const PushMessage(title: 'مبروك! رتبة جديدة'));
    await tester.pumpAndSettle();

    expect(find.text('تعليم الكل كمقروء'), findsOneWidget);
  });

  testWidgets('a push that launched the app opens its order', (tester) async {
    await pumpApp(
      tester,
      session: _saved,
      launchPush: const PushMessage(orderNumber: 'MQ-DONE-2'),
    );

    expect(find.text('تفاصيل الطلب'), findsOneWidget);
    expect(find.text('MQ-DONE-2'), findsOneWidget);
  });
}
