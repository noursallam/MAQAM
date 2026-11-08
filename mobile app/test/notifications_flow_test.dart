import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/notifications/data/api_notifications_repository.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

void main() {
  test('maps the feed, the unread count and what each notification is about', () async {
    final requests = <http.Request>[];
    final repository = ApiNotificationsRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          requests.add(request);
          return http.Response.bytes(
            utf8.encode(jsonEncode({
              'success': true,
              'message': '',
              'data': request.method == 'GET'
                  ? [
                      {
                        'id': 7,
                        'title': 'تحديث الطلب',
                        'body': 'تم الشحن',
                        'type': 'order_update',
                        'data': {'order_number': 'MQ-1', 'status': 'shipped'},
                        'is_read': false,
                        'created_at': '2026-10-08T09:00:00+00:00',
                      },
                      // An empty `data` can arrive as a list rather than an object.
                      {'id': 6, 'title': 'عرض', 'body': 'خصم', 'type': 'offer', 'data': [], 'is_read': true},
                    ]
                  : null,
              'meta': {'current_page': 1, 'last_page': 1, 'per_page': 30, 'total': 2, 'unread_count': 5},
            })),
            200,
          );
        }),
        token: () => 't',
      ),
    );

    final feed = await repository.feed();
    await repository.markRead(7);
    await repository.markAllRead();
    await repository.registerDevice(token: 'fcm-token', platform: 'android');

    expect(feed.unreadCount, 5);
    expect(feed.items.first.orderNumber, 'MQ-1');
    expect(feed.items.first.isRead, isFalse);
    expect(feed.items.last.orderNumber, isNull);
    expect(requests.map((r) => '${r.method} ${r.url.path.split('/v1').last}'), [
      'GET /notifications',
      'POST /notifications/7/read',
      'POST /notifications/read-all',
      'POST /notifications/device-token',
    ]);
    expect(jsonDecode(requests.last.body), {'device_token': 'fcm-token', 'platform': 'android'});
  });

  testWidgets('unread notifications are flagged, and tapping one opens its order',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);

    // The bell on the home screen carries a dot, and the account row a count.
    expect(tester.widget<Badge>(find.byType(Badge).first).isLabelVisible, isTrue);
    await openTab(tester, 'حسابي');
    expect(find.text('1 جديدة'), findsOneWidget);

    await tester.tap(find.text('الإشعارات'));
    await tester.pumpAndSettle();
    expect(find.text('تحديث الطلب MQ-ACTIVE-1'), findsOneWidget);
    expect(find.text('الآن'), findsOneWidget);
    expect(find.text('تمت ترقيتك إلى رتبة ذهبي.'), findsOneWidget);
    expect(find.text('تعليم الكل كمقروء'), findsOneWidget);

    await tester.tap(find.text('تحديث الطلب MQ-ACTIVE-1'));
    await tester.pumpAndSettle();

    expect(app.notifications.read, [1]);
    expect(find.text('تفاصيل الطلب'), findsOneWidget);

    // Back on the list nothing is unread any more.
    await tester.tap(find.byType(BackButton));
    await tester.pumpAndSettle();
    expect(find.text('تعليم الكل كمقروء'), findsNothing);
  });

  testWidgets('everything can be marked as read at once', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await tester.tap(find.byTooltip('الإشعارات'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('تعليم الكل كمقروء'));
    await tester.pumpAndSettle();

    expect(app.notifications.allRead, isTrue);
    expect(find.text('تعليم الكل كمقروء'), findsNothing);
  });

  testWidgets('a guest who taps the bell is taken to sign-in', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await tester.tap(find.byTooltip('الإشعارات'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الجوال'), findsOneWidget);
  });
}
