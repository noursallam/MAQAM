import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

Future<void> _openScanner(WidgetTester tester) async {
  await tester.tap(find.byTooltip('مسح كود'));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('without a connection a scan is kept, then uploaded once back online', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.scan.offline = true;
    await _openScanner(tester);

    await tester.tap(find.text('معرض الأمل'));
    await tester.pump();
    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();

    expect(find.text('لا يوجد اتصال: المسح يُحفظ على هاتفك الآن'), findsOneWidget);
    expect(find.text('تم حفظ المسح'), findsOneWidget);
    expect(find.text('الكود AL-1 •••• · معرض الأمل'), findsOneWidget);
    expect(app.store.values['scan_queue'], contains('MQ-SERIAL-1'));

    // The same pack read twice is kept once.
    await tester.tap(find.text('مسح كود آخر'));
    await tester.pump();
    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('في الانتظار (1)'));
    await tester.pumpAndSettle();

    expect(find.text('مسحة واحدة في انتظار الرفع'), findsOneWidget);
    expect(find.textContaining('معرض الأمل'), findsOneWidget);

    await tester.tap(find.text('رفع الآن'));
    await tester.pumpAndSettle();
    expect(find.text('لا يوجد اتصال بعد. سنحاول مرة أخرى تلقائياً.'), findsOneWidget);

    app.scan.offline = false;
    await tester.tap(find.text('رفع الآن'));
    await tester.pumpAndSettle();

    expect(app.scan.uploaded.single.single.serialCode, 'MQ-SERIAL-1');
    expect(app.scan.uploaded.single.single.merchantCode, 'M-AMAL');
    expect(find.text('+25'), findsOneWidget);
    expect(find.text('آخر عملية رفع'), findsOneWidget);
    expect(find.text('رفع الآن'), findsNothing);
  });

  testWidgets('waiting scans are counted in the account and rejected ones can be hidden',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.scan.offline = true;
    await _openScanner(tester);
    await tester.tap(find.text('camera sees BAD'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('إغلاق'));
    await tester.pumpAndSettle();

    await openTab(tester, 'حسابي');
    expect(find.text('1'), findsOneWidget);
    await tester.tap(find.text('مسحات في انتظار الرفع'));
    await tester.pumpAndSettle();

    app.scan.offline = false;
    await tester.tap(find.text('رفع الآن'));
    await tester.pumpAndSettle();
    expect(find.text('لم يُقبل: الكود ممسوح مسبقاً.'), findsOneWidget);

    await tester.tap(find.text('إخفاء'));
    await tester.pumpAndSettle();
    expect(find.text('لا توجد مسحات في الانتظار'), findsOneWidget);
  });

  testWidgets('signing out on purpose forgets waiting scans', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.scan.offline = true;
    await _openScanner(tester);
    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('إغلاق'));
    await tester.pumpAndSettle();

    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('تسجيل الخروج'));
    await tester.tap(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('تسجيل الخروج').last);
    await tester.pumpAndSettle();

    expect(app.store.values['scan_queue'], isNull);
  });
}
