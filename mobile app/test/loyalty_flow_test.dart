import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

Future<void> _openRewards(WidgetTester tester) async {
  await tester.tap(find.text('مكافآتي'));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('rewards list each prize, and one can be used in the cart', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openRewards(tester);

    expect(find.text('خصم 10% على طلبك'), findsOneWidget);
    expect(find.text('من عجلة الحظ · ينتهي خلال 12 يوماً'), findsOneWidget);
    expect(find.text('منتج هدية: مفتاح تجريبي'), findsOneWidget);
    expect(find.text('من عجلة الحظ · يُشحن مجاناً مع أي طلب'), findsOneWidget);
    expect(find.text('GP-4471KLMN'), findsOneWidget);

    await tester.tap(find.text('استخدم في السلة').first);
    await tester.pumpAndSettle();

    expect(app.cart.coupon, 'WD-9812AXYZ');
    expect(find.text('مكافآتي'), findsNothing);
  });

  testWidgets('used and expired rewards have their own tabs', (tester) async {
    await pumpApp(tester, session: _saved);
    await _openRewards(tester);

    await tester.tap(find.text('مستخدمة'));
    await tester.pumpAndSettle();
    expect(find.text('CP-USED0001'), findsOneWidget);
    expect(find.text('استخدم في السلة'), findsNothing);

    await tester.tap(find.text('منتهية'));
    await tester.pumpAndSettle();
    expect(find.text('لا توجد مكافآت هنا'), findsOneWidget);
  });

  testWidgets('rewards that fail to load offer a retry', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.loyalty.failRewards = true;
    await _openRewards(tester);

    expect(find.text('تعذر تحميل المكافآت'), findsOneWidget);

    app.loyalty.failRewards = false;
    await tester.tap(find.text('إعادة المحاولة'));
    await tester.pumpAndSettle();
    expect(find.text('WD-9812AXYZ'), findsOneWidget);
  });

  testWidgets('a spin of the wheel lands on its prize and says what was won', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await tester.tap(find.text('عجلة الحظ'));
    await tester.pumpAndSettle();

    expect(find.text('تكلفة اللفة'), findsOneWidget);
    expect(find.text('50 نقطة'), findsOneWidget);
    expect(find.text('350 نقطة'), findsOneWidget);

    await tester.tap(find.text('لفّ العجلة'));
    await tester.pump();
    expect(find.text('العجلة تدور…'), findsOneWidget);
    await tester.pumpAndSettle();

    expect(app.loyalty.spins, 1);
    expect(find.text('مبروك!'), findsOneWidget);
    expect(find.text('ربحت 50 نقطة.'), findsOneWidget);

    await tester.tap(find.text('حسناً'));
    await tester.pumpAndSettle();
    expect(find.text('لفّ العجلة'), findsOneWidget);
  });

  testWidgets('without enough points the wheel is shown locked and cannot spin', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.loyalty.canSpin = false;
    await tester.tap(find.text('عجلة الحظ'));
    await tester.pumpAndSettle();

    expect(find.bySemanticsLabel('عجلة الحظ مقفلة'), findsOneWidget);
    expect(find.text('تحتاج 50 نقطة على الأقل للّفة.'), findsOneWidget);

    await tester.tap(find.text('لفّ العجلة'));
    await tester.pumpAndSettle();
    expect(app.loyalty.spins, 0);
  });

  testWidgets('the wallet opens the wheel and the rewards', (tester) async {
    await pumpApp(tester, session: _saved);
    await openTab(tester, 'المحفظة');

    await tester.tap(find.text('عجلة الحظ'));
    await tester.pumpAndSettle();
    expect(find.text('لفّ العجلة'), findsOneWidget);

    // The wheel's footer links to the rewards.
    await tester.tapOnText(find.textRange.ofSubstring('مكافآتي'));
    await tester.pumpAndSettle();
    expect(find.text('جرّب حظك في العجلة'), findsOneWidget);
  });
}
