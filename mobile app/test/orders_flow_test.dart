import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/orders/data/api_orders_repository.dart';
import 'package:maqam_app/features/orders/domain/order.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

void main() {
  test('maps an order with its items and address, folding server statuses', () async {
    final repository = ApiOrdersRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          final status = request.url.pathSegments.last == 'MQ-NEW' ? 'new' : 'refunded';
          return http.Response.bytes(
            utf8.encode(jsonEncode({
              'success': true,
              'message': '',
              'data': {
                'order_number': request.url.pathSegments.last,
                'status': status,
                'payment_method': 'kashier',
                'subtotal': '150.00',
                'discount': '10.00',
                'shipping_cost': '35.00',
                'total_amount': '175.00',
                'can_cancel': true,
                'created_at': '2026-10-01T10:00:00+00:00',
                'shipping_address': {
                  'recipient_name': 'أحمد',
                  'phone': '01012345678',
                  'governorate': 'القاهرة',
                  'city': 'مدينة نصر',
                  'address': 'شارع الطيران',
                },
                'items': [
                  {
                    'product': {'name': 'مفتاح', 'image_url': null},
                    'option_label': 'أسود',
                    'quantity': 2,
                    'unit_price': '75.00',
                    'subtotal': '150.00',
                  },
                  {'product': null, 'option_label': null, 'quantity': 1, 'subtotal': 0},
                ],
              },
            })),
            200,
          );
        }),
        token: () => 't',
      ),
    );

    final details = await repository.order('MQ-NEW');

    expect(details.order.status, OrderStatus.received);
    expect(details.order.total, 175);
    expect(details.order.canCancel, isTrue);
    // Paid by card and no payment recorded yet; a cancelled one is no longer waiting.
    expect(details.order.awaitingPayment, isTrue);
    expect(details.subtotal, 150);
    expect(details.discount, 10);
    expect(details.shipping, 35);
    expect(details.addressLine, 'القاهرة، مدينة نصر، شارع الطيران');
    expect(details.items.first.lineTotal, 150);
    expect(details.items.last.name, '');
    expect(details.items.last.lineTotal, 0);
    final old = (await repository.order('MQ-OLD')).order;
    expect(old.status, OrderStatus.cancelled);
    expect(old.awaitingPayment, isFalse);
  });

  testWidgets('orders are listed, filtered, opened and cancelled', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await openTab(tester, 'حسابي');
    await tester.tap(find.text('طلباتي'));
    await tester.pumpAndSettle();

    expect(find.text('MQ-ACTIVE-1'), findsOneWidget);
    expect(find.text('MQ-DONE-2'), findsOneWidget);
    expect(find.text('اليوم · عند الاستلام'), findsOneWidget);
    expect(find.text('جارٍ التجهيز'), findsOneWidget);
    // An unfinished card payment is not shown as an order on its way.
    expect(find.text('MQ-UNPAID-3'), findsOneWidget);
    expect(find.text('بانتظار الدفع'), findsOneWidget);

    await tester.tap(find.text('مكتملة'));
    await tester.pumpAndSettle();
    expect(find.text('MQ-ACTIVE-1'), findsNothing);
    expect(find.text('MQ-DONE-2'), findsOneWidget);

    await tester.tap(find.text('جارية'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('MQ-ACTIVE-1'));
    await tester.pumpAndSettle();

    expect(find.text('تفاصيل الطلب'), findsOneWidget);
    // The four steps of the timeline, plus the items and the address.
    for (final step in ['الاستلام', 'التجهيز', 'التسليم']) {
      expect(find.text(step), findsOneWidget);
    }
    // "الشحن" is both a step and the shipping line of the summary.
    expect(find.text('الشحن'), findsNWidgets(2));
    expect(find.text('الكمية 2 · أسود'), findsOneWidget);
    expect(find.text('مجاناً'), findsOneWidget);
    expect(find.text('القاهرة، مدينة نصر'), findsOneWidget);
    expect(find.text('185.00 ج.م'), findsOneWidget);

    await tester.tap(find.widgetWithText(OutlinedButton, 'إلغاء الطلب'));
    await tester.pumpAndSettle();
    expect(find.text('إلغاء هذا الطلب؟'), findsOneWidget);
    await tester.tap(find.text('الإبقاء على الطلب'));
    await tester.pumpAndSettle();
    expect(app.orders.cancelled, isEmpty);

    await tester.tap(find.widgetWithText(OutlinedButton, 'إلغاء الطلب'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(TextButton, 'إلغاء الطلب'));
    await tester.pumpAndSettle();

    expect(app.orders.cancelled, ['MQ-ACTIVE-1']);
    expect(find.text('ملغي'), findsOneWidget);
    // Cancelled: no timeline, and nothing left to cancel.
    expect(find.text('التجهيز'), findsNothing);
    expect(find.widgetWithText(OutlinedButton, 'إلغاء الطلب'), findsNothing);
  });

  testWidgets('a delivered order cannot be cancelled', (tester) async {
    await pumpApp(tester, session: _saved);
    await tester.tap(find.text('طلباتي'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('MQ-DONE-2'));
    await tester.pumpAndSettle();

    expect(find.text('تم التسليم'), findsOneWidget);
    expect(find.text('إلغاء الطلب'), findsNothing);
  });

  testWidgets('a guest who taps orders is taken to sign-in', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('طلباتي'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الجوال'), findsOneWidget);
  });
}
