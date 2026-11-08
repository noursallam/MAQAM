import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/core/push/push_messaging.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/seminars/data/api_seminar_repository.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

ApiSeminarRepository _repository(MockClientHandler handler) =>
    ApiSeminarRepository(ApiClient(httpClient: MockClient(handler), token: () => 't'));

const _seminarJson = {
  'id': 3,
  'title': 'سيمينار القاهرة',
  'description': '',
  'location': 'فندق النيل',
  'image_url': null,
  'starts_at': '2026-11-01T16:00:00+00:00',
  'ends_at': '2026-11-01T19:00:00+00:00',
  'status': 'live',
  'registrations_count': 12,
  'is_registered': true,
  'draws': [
    {'position': 1, 'prize': 'تليفزيون', 'is_drawn': true, 'winner_name': 'أحمد •••678', 'is_mine': true, 'drawn_at': '2026-11-01T17:00:00+00:00'},
    {'position': 2, 'prize': 'شنيور', 'is_drawn': false, 'winner_name': null, 'is_mine': false, 'drawn_at': null},
  ],
};

void main() {
  test('maps the running seminar, and none at all', () async {
    final repository = _repository((request) async => _json({'success': true, 'message': '', 'data': _seminarJson}));

    final seminar = await repository.current();

    expect(seminar?.id, 3);
    expect(seminar?.isLive, isTrue);
    expect(seminar?.description, isNull);
    expect(seminar?.location, 'فندق النيل');
    expect(seminar?.registrations, 12);
    expect(seminar?.draws.map((d) => d.prize), ['تليفزيون', 'شنيور']);
    expect(seminar?.myWin?.prize, 'تليفزيون');
    expect(seminar?.startsAt.isUtc, isFalse);

    final none = _repository((_) async => _json({'success': true, 'message': '', 'data': null}));
    expect(await none.current(), isNull);
  });

  test('signs up with a POST and reports a closed seminar', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({'success': false, 'message': 'التسجيل غير متاح.', 'error_code': 'SEMINAR_NOT_OPEN'}, 422);
    });

    await expectLater(
      repository.register(3),
      throwsA(isA<ApiException>().having((e) => e.code, 'code', 'SEMINAR_NOT_OPEN')),
    );
    expect(sent.method, 'POST');
    expect(sent.url.path, endsWith('/seminars/3/register'));
  });

  testWidgets('a running seminar is promoted on the home page and a customer joins its draw',
      (tester) async {
    final app = await pumpApp(tester, session: _saved, seminarLive: true);

    expect(find.text('سيمينار القاهرة'), findsOneWidget);
    expect(find.text('سحب على 3 جوائز'), findsOneWidget);
    expect(find.text('جارٍ الآن'), findsOneWidget);

    await tester.tap(find.text('سيمينار القاهرة'));
    await tester.pumpAndSettle();

    expect(find.text('فندق النيل'), findsOneWidget);
    expect(find.text('الفائز: محمد •••321'), findsOneWidget);
    expect(find.text('لم تُسحب بعد'), findsNWidgets(2));

    await tester.tap(find.text('سجّل في السحب'));
    await tester.pumpAndSettle();

    expect(app.seminars.registrations, [7]);
    expect(find.text('أنت مسجّل في السحب. تابع النتائج هنا.'), findsOneWidget);
    expect(find.text('42 مسجل'), findsOneWidget);
  });

  testWidgets('new winners appear while the page is open, and the winner is congratulated',
      (tester) async {
    final app = await pumpApp(tester, session: _saved, seminarLive: true);
    app.seminars.registered = true;
    await tester.tap(find.text('سيمينار القاهرة'));
    await tester.pumpAndSettle();
    expect(find.text('مبروك! فزت بـ شنيور'), findsNothing);

    app.seminars.won = true;
    await tester.pump(const Duration(seconds: 20));
    await tester.pumpAndSettle();

    expect(find.text('مبروك! فزت بـ شنيور'), findsOneWidget);
    expect(find.text('الفائز: أنت!'), findsOneWidget);
  });

  testWidgets('nothing is promoted when no seminar is running', (tester) async {
    await pumpApp(tester, session: _saved);

    expect(find.text('سيمينار القاهرة'), findsNothing);
  });

  testWidgets('a guest sees the seminar and is asked to sign in to join', (tester) async {
    await pumpApp(tester, seminarLive: true);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('سيمينار القاهرة'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('سجّل في السحب'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الجوال'), findsOneWidget);
  });

  testWidgets("tapping a winner's push opens the seminar", (tester) async {
    await pumpApp(
      tester,
      session: _saved,
      seminarLive: true,
      launchPush: const PushMessage(title: 'مبروك!', seminarId: 7),
    );

    expect(find.text('الجوائز والفائزون'), findsOneWidget);
  });
}
