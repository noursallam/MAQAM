import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

void main() {
  testWidgets('a version below the minimum stops at the update screen', (tester) async {
    await pumpApp(tester, session: _saved, updateRequired: true);

    expect(find.text('يوجد إصدار جديد'), findsOneWidget);
    expect(find.text('إصدارك 1.0.0 · المطلوب 9.0.0'), findsOneWidget);
    expect(find.text('تحديث الآن'), findsOneWidget);
    // The bottom bar is not reachable behind it.
    expect(find.text('المحفظة'), findsNothing);
  });

  testWidgets('a supported version goes straight in', (tester) async {
    await pumpApp(tester, session: _saved);

    expect(find.text('يوجد إصدار جديد'), findsNothing);
    expect(find.text('مفتاح تجريبي'), findsOneWidget);
  });

  testWidgets('a scan that trips the fraud check shows the suspended screen until sign-out',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.scan.freezeNext = true;
    await tester.tap(find.byTooltip('مسح كود'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();

    expect(find.text('الحساب موقوف مؤقتاً'), findsOneWidget);
    expect(find.text('التواصل مع الدعم'), findsOneWidget);

    await tester.tap(find.text('تسجيل الخروج'));
    await tester.pumpAndSettle();

    expect(app.auth.signedOut, isTrue);
    expect(find.text('الفخامة في كل لمسة'), findsOneWidget);
  });
}
