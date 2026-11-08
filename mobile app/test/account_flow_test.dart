import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/merchant/domain/merchant.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

Future<void> _openAccountRow(WidgetTester tester, String label) async {
  await openTab(tester, 'حسابي');
  await tester.ensureVisible(find.text(label));
  await tester.pumpAndSettle();
  await tester.tap(find.text(label));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('the name can be edited from the profile card', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');

    await tester.tap(find.byTooltip('تعديل'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), 'أ');
    await tester.tap(find.text('حفظ'));
    await tester.pump();
    expect(find.text('اكتب اسمك (حرفان على الأقل).'), findsOneWidget);

    await tester.enterText(find.byType(TextField), 'محمد سالم');
    await tester.tap(find.text('حفظ'));
    await tester.pumpAndSettle();

    expect(app.account.savedName, 'محمد سالم');
    expect(find.text('محمد سالم'), findsOneWidget);
    expect(app.store.values['session'], contains('محمد سالم'));
  });

  testWidgets('addresses can be added, made default and deleted', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openAccountRow(tester, 'عناويني');

    expect(find.text('أحمد محمود'), findsOneWidget);
    expect(find.text('الافتراضي'), findsOneWidget);

    await tester.tap(find.text('حفظ العنوان'));
    await tester.pump();
    expect(find.text('هذا الحقل مطلوب.'), findsWidgets);

    final fields = find.byType(TextFormField);
    await tester.enterText(fields.at(0), 'أحمد (العمل)');
    await tester.enterText(fields.at(1), '01112345678');
    await tester.enterText(fields.at(2), 'الجيزة');
    await tester.enterText(fields.at(3), 'المهندسين');
    await tester.enterText(fields.at(4), 'شارع جامعة الدول 5');
    await tester.tap(find.text('حفظ العنوان'));
    await tester.pumpAndSettle();

    expect(app.addressBook.saved, hasLength(2));
    expect(find.text('أحمد (العمل)'), findsOneWidget);

    await tester.tap(find.text('تعيين كافتراضي'));
    await tester.pumpAndSettle();
    expect(app.addressBook.saved.first.recipientName, 'أحمد (العمل)');

    await tester.tap(find.text('حذف').last);
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(TextButton, 'حذف').last);
    await tester.pumpAndSettle();
    expect(app.addressBook.saved.map((a) => a.recipientName), ['أحمد (العمل)']);
  });

  testWidgets('addresses that fail to load offer a retry', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.addressBook.fail = true;
    await _openAccountRow(tester, 'عناويني');

    expect(find.text('تعذر تحميل العناوين'), findsOneWidget);
    expect(find.text('إعادة المحاولة'), findsOneWidget);
  });

  testWidgets('deleting the account needs the word typed, then leaves the account', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');
    await tester.ensureVisible(find.text('حذف الحساب'));
    await tester.tap(find.text('حذف الحساب'));
    await tester.pumpAndSettle();

    expect(find.text('رصيد نقاطك (400 نقطة) ورتبتك'), findsOneWidget);
    final submit = find.widgetWithText(FilledButton, 'حذف حسابي نهائياً');
    expect(tester.widget<FilledButton>(submit).onPressed, isNull);

    await tester.enterText(find.byType(TextField), 'delete');
    await tester.pump();
    await tester.tap(submit);
    await tester.pumpAndSettle();

    expect(app.account.deleted, isTrue);
    expect(app.store.values.containsKey('session'), isFalse);
    expect(find.text('الفخامة في كل لمسة'), findsOneWidget);
  });

  testWidgets('the phone number is changed by proving the new one on WhatsApp', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openAccountRow(tester, 'تغيير رقم الجوال');

    expect(find.text('رقم الجوال الجديد'), findsWidgets);
    await tester.enterText(find.byType(TextField), '1212345678');
    await tester.tap(find.text('متابعة'));
    await tester.pump();
    await tester.pump();

    expect(app.phoneChange.startedPhone, '01212345678');
    expect(find.text('MAQAM-111222'), findsOneWidget);

    await tester.pump(const Duration(seconds: 4));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), '654321');
    await tester.pumpAndSettle();

    expect(find.text('تم تغيير رقم الجوال.'), findsOneWidget);
    expect(find.text('012 1234 5678'), findsOneWidget);
    expect(app.store.values['session'], contains('01212345678'));
  });

  testWidgets('a customer applies to be a merchant and sees the request under review', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');
    await tester.tap(find.text('الانضمام كتاجر معتمد'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('إرسال الطلب'));
    await tester.pump();
    expect(find.text('هذا الحقل مطلوب.'), findsOneWidget);

    await tester.enterText(find.byType(TextFormField).first, 'معرض النور');
    await tester.tap(find.text('رفع صورة'));
    await tester.pumpAndSettle();
    expect(find.text('logo.png'), findsOneWidget);

    await tester.tap(find.text('إرسال الطلب'));
    await tester.pumpAndSettle();

    expect(app.merchant.applied?.name, 'معرض النور');
    expect(app.merchant.applied?.logo?.fileName, 'logo.png');
    expect(find.text('لوحة التاجر'), findsOneWidget);
    expect(find.text('M-AB12CD34'), findsOneWidget);
    expect(find.text('قيد المراجعة'), findsOneWidget);
    // Nothing to share until the code works.
    expect(find.text('مشاركة'), findsNothing);
  });

  testWidgets('an approved merchant sees their code, statistics and who scanned with it', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.merchant
      ..current = MerchantProfile(
        code: 'M-AB12CD34',
        businessName: 'معرض النور',
        approved: true,
        stats: const MerchantStats(
          totalScans: 120,
          totalPoints: 600,
          scansThisMonth: 18,
          pointsThisMonth: 90,
          uniqueCustomers: 37,
        ),
        recentScans: [
          MerchantScan(
            id: 1,
            points: 5,
            customerName: 'منى علي',
            customerPhone: '011****432',
            prize: 'عبوة ذهبية',
            scannedAt: DateTime.now(),
          ),
        ],
      )
      ..scanPages = [
        [const MerchantScan(id: 1, points: 5, customerName: 'منى علي')],
        [const MerchantScan(id: 2, points: 5, customerName: 'أحمد سمير')],
      ];
    await openTab(tester, 'حسابي');

    // The profile tab already marks the account as a merchant and shows the code and this month
    expect(find.text('الانضمام كتاجر معتمد'), findsNothing);
    expect(find.text('تاجر معتمد'), findsOneWidget);
    expect(find.text('معرض النور'), findsOneWidget);
    expect(find.text('M-AB12CD34'), findsOneWidget);
    expect(find.text('90'), findsOneWidget);

    await tester.tap(find.text('معرض النور'));
    await tester.pumpAndSettle();
    expect(find.text('مشاركة'), findsOneWidget);
    expect(find.text('120'), findsOneWidget);
    expect(find.text('37'), findsOneWidget);
    expect(find.text('منى علي'), findsOneWidget);
    expect(find.text('011****432'), findsOneWidget);
    expect(find.text('+5 نقطة'), findsOneWidget);

    await tester.tap(find.text('عرض الكل'));
    await tester.pumpAndSettle();
    expect(find.text('المسحات بكودك'), findsOneWidget);
    expect(find.text('أحمد سمير'), findsNothing);
    await tester.tap(find.text('تحميل المزيد'));
    await tester.pumpAndSettle();
    expect(find.text('أحمد سمير'), findsOneWidget);
    expect(find.text('تحميل المزيد'), findsNothing);
  });

  testWidgets('the merchant page offers a retry when it fails to load', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    app.merchant.fail = true;
    await openTab(tester, 'حسابي');
    await tester.tap(find.text('الانضمام كتاجر معتمد'));
    await tester.pumpAndSettle();

    expect(find.text('تعذر تحميل بيانات التاجر'), findsOneWidget);
    expect(find.text('إعادة المحاولة'), findsOneWidget);
  });

  testWidgets('the legal pages open from the account and switch between them', (tester) async {
    await pumpApp(tester, session: _saved);
    await _openAccountRow(tester, 'الشروط وسياسة الخصوصية');

    expect(find.text('الشروط والأحكام'), findsWidgets);
    expect(find.text('المنتجات والطلبات'), findsOneWidget);

    await tester.tap(find.text('سياسة الخصوصية').first);
    await tester.pumpAndSettle();
    expect(find.text('سياسة الخصوصية'), findsWidgets);
  });

  testWidgets('the assistant answers and says how many questions are left', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openAccountRow(tester, 'المساعد والأسئلة الشائعة');

    expect(find.text('أهلاً بك! كيف أقدر أساعدك؟'), findsOneWidget);

    await tester.tap(find.text('إزاي أكسب نقاط؟'));
    await tester.pumpAndSettle();

    expect(app.content.questions.single.message, 'إزاي أكسب نقاط؟');
    expect(find.text('الشحن 35 جنيهاً للطلب.'), findsOneWidget);
    expect(find.text('متبقي 59 رسالة اليوم'), findsOneWidget);

    await tester.enterText(find.byType(TextField), 'FAIL');
    await tester.tap(find.byTooltip('إرسال'));
    await tester.pumpAndSettle();
    expect(find.text('المساعد غير متاح حالياً.'), findsOneWidget);
    expect(find.text('FAIL'), findsOneWidget);
  });

  testWidgets('the terms open from the sign-in page', (tester) async {
    await pumpApp(tester);
    await openLogin(tester);

    await tester.tapOnText(find.textRange.ofSubstring('الشروط والأحكام'));
    await tester.pumpAndSettle();

    expect(find.text('المنتجات والطلبات'), findsOneWidget);
  });
}
