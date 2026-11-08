import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../support/test_app.dart';

void main() {
  testWidgets('the tour walks through five slides and ends at sign-in', (tester) async {
    await pumpApp(tester);

    const titles = [
      'الفخامة في كل لمسة',
      'امسح واكسب',
      'رتبتك ترتفع مع نقاطك',
      'عجلة الحظ ومكافآت',
      'دخول آمن بلا كلمة مرور',
    ];

    for (final (index, title) in titles.indexed) {
      expect(find.text(title), findsOneWidget);
      expect(find.bySemanticsLabel('الشاشة ${index + 1} من 5'), findsOneWidget);

      if (index < titles.length - 1) {
        expect(find.text('تخطي'), findsOneWidget);
        await tester.tap(find.text('التالي'));
        await tester.pumpAndSettle();
      }
    }

    // The last slide swaps "next" for the call to action and drops "skip".
    expect(find.text('التالي'), findsNothing);
    // Skip keeps its space so the logo does not move, but it is hidden and inert.
    expect(find.text('تخطي').hitTestable(), findsNothing);

    await tester.tap(find.text('ابدأ الآن'));
    await tester.pumpAndSettle();
    expect(find.text('رقم الجوال'), findsOneWidget);
  });

  testWidgets('the tour can be swiped, following the reading direction', (tester) async {
    await pumpApp(tester);

    // Arabic reads right to left, so the next slide comes in from the left.
    await tester.drag(find.byType(PageView), const Offset(300, 0));
    await tester.pumpAndSettle();

    expect(find.text('امسح واكسب'), findsOneWidget);
  });

  testWidgets('quick taps on next each advance a slide', (tester) async {
    await pumpApp(tester);

    await tester.tap(find.text('التالي'));
    await tester.pump(const Duration(milliseconds: 50));
    await tester.tap(find.text('التالي'));
    await tester.pump(const Duration(milliseconds: 50));
    await tester.tap(find.text('التالي'));
    await tester.pumpAndSettle();

    expect(find.text('عجلة الحظ ومكافآت'), findsOneWidget);

    // Swiping back and tapping next goes one slide forward from where you are.
    await tester.drag(find.byType(PageView), const Offset(-300, 0));
    await tester.pumpAndSettle();
    expect(find.text('رتبتك ترتفع مع نقاطك'), findsOneWidget);
    await tester.tap(find.text('التالي'));
    await tester.pumpAndSettle();
    expect(find.text('عجلة الحظ ومكافآت'), findsOneWidget);
  });

  testWidgets('skip and guest browsing work from any slide', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('التالي'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    expect(find.text('اكسب نقاطاً مع كل عبوة'), findsOneWidget);
  });
}
