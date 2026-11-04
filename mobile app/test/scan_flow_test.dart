import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/scan/data/api_scan_repository.dart';
import 'package:maqam_app/features/scan/domain/scan.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678');

Future<void> _openScanner(WidgetTester tester) async {
  await tester.tap(find.byTooltip('مسح كود'));
  await tester.pumpAndSettle();
}

void main() {
  test('reads the serial from a bare code or from a link ending in it', () {
    expect(serialFromScan('  MQ-ABC123 '), 'MQ-ABC123');
    expect(serialFromScan('https://maqam-eg.com/q/MQ-ABC123'), 'MQ-ABC123');
    expect(serialFromScan('https://maqam-eg.com/scan?code=MQ-ABC123'), 'MQ-ABC123');
  });

  test('claims a code and maps what it gave', () async {
    late http.Request sent;
    final repository = ApiScanRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          sent = request;
          return http.Response.bytes(
            utf8.encode(jsonEncode({
              'success': true,
              'message': '',
              'data': {
                'scan_id': 7,
                'points_awarded': 25,
                'new_balance': 425,
                'total_earned': 475,
                'merchant_credited': 'معرض الأمل',
                'merchant_points': 5,
                'account_frozen': false,
              },
            })),
            200,
          );
        }),
        token: () => 't',
      ),
    );

    final result = await repository.scan(serialCode: 'MQ-1', merchantCode: 'M-AMAL');

    expect(sent.url.path, endsWith('/qr/scan'));
    expect(jsonDecode(sent.body), {'serial_code': 'MQ-1', 'merchant_code': 'M-AMAL'});
    expect(result.pointsAwarded, 25);
    expect(result.newBalance, 425);
    expect(result.merchantName, 'معرض الأمل');
    expect(result.merchantPoints, 5);
  });

  test('uploads offline scans and maps each outcome; unanswered ones stay out', () async {
    late Map<String, dynamic> body;
    final repository = ApiScanRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          body = jsonDecode(request.body) as Map<String, dynamic>;
          return http.Response.bytes(
            utf8.encode(jsonEncode({
              'success': true,
              'message': '',
              'data': {
                'total_synced': 1,
                'total_failed': 1,
                'current_balance': 450,
                'results': [
                  {'serial_code': 'S-1', 'status': 'success', 'points': 25, 'error_code': null, 'message': null},
                  {'serial_code': 'S-2', 'status': 'failed', 'points': 0, 'error_code': 'QR_ALREADY_USED', 'message': 'مستخدم'},
                  {'serial_code': 'S-3', 'status': 'already_synced', 'points': 0, 'error_code': null, 'message': null},
                ],
              },
            })),
            200,
          );
        }),
        token: () => 't',
      ),
    );
    final at = DateTime.utc(2026, 10, 20, 10, 15);

    final results = await repository.sync([
      QueuedScan(serialCode: 'S-1', scannedAt: at, merchantCode: 'M-AMAL'),
      QueuedScan(serialCode: 'S-2', scannedAt: at),
      QueuedScan(serialCode: 'S-3', scannedAt: at),
      QueuedScan(serialCode: 'S-4', scannedAt: at),
    ]);

    expect((body['scans'] as List).first, {
      'serial_code': 'S-1',
      'merchant_code': 'M-AMAL',
      'scanned_at': '2026-10-20T10:15:00.000Z',
    });
    expect(results.map((r) => r.status),
        [SyncStatus.credited, SyncStatus.rejected, SyncStatus.alreadyCredited]);
    expect(results.first.points, 25);
    expect(results[1].message, 'مستخدم');
  });

  testWidgets('scanning a code shows the points earned and the new balance', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openScanner(tester);

    expect(find.text('مسح كود QR'), findsOneWidget);
    expect(find.text('من أي تاجر اشتريت؟'), findsOneWidget);

    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();

    expect(app.scan.claimed.single, (serialCode: 'MQ-SERIAL-1', merchantCode: null));
    expect(find.textContaining('+25'), findsOneWidget);
    expect(find.text('425 نقطة'), findsOneWidget);
    expect(find.text('باقي لرتبة ذهبي'), findsOneWidget);
    // No merchant was named, so none is shown.
    expect(find.text('التاجر'), findsNothing);

    await tester.tap(find.text('عرض المحفظة'));
    await tester.pumpAndSettle();
    expect(find.text('متاح للصرف'), findsOneWidget);
  });

  testWidgets('a recent merchant can be credited, and a link is reduced to its code',
      (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openScanner(tester);

    await tester.tap(find.text('معرض الأمل'));
    await tester.pump();
    await tester.tap(find.text('camera sees https://maqam-eg.com/q/MQ-SERIAL-2'));
    await tester.pumpAndSettle();

    expect(app.scan.claimed.single, (serialCode: 'MQ-SERIAL-2', merchantCode: 'M-AMAL'));
    expect(find.text('معرض الأمل'), findsOneWidget);
    expect(find.text('+5'), findsOneWidget);

    await tester.tap(find.text('مسح كود آخر'));
    await tester.pumpAndSettle();
    expect(find.text('مسح كود QR'), findsOneWidget);
  });

  testWidgets('a rejected code says why and lets the customer try again', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openScanner(tester);

    await tester.tap(find.text('camera sees BAD'));
    await tester.pumpAndSettle();
    expect(find.text('الكود ممسوح مسبقاً.'), findsOneWidget);
    expect(app.scan.claimed, isEmpty);

    await tester.tap(find.text('camera sees MQ-SERIAL-1'));
    await tester.pumpAndSettle();
    expect(find.textContaining('+25'), findsOneWidget);
  });

  testWidgets('a code can be typed in, with a merchant code added by hand', (tester) async {
    final app = await pumpApp(tester, session: _saved);
    await _openScanner(tester);

    await tester.enterText(find.byType(TextField), 'm-new99');
    await tester.tap(find.text('إضافة'));
    await tester.pump();
    expect(find.text('m-new99'), findsOneWidget);

    await tester.tap(find.text('إدخال يدوي'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField).last, 'MQ-TYPED');
    await tester.tap(find.text('تأكيد'));
    await tester.pumpAndSettle();

    expect(app.scan.claimed.single, (serialCode: 'MQ-TYPED', merchantCode: 'm-new99'));
  });

  testWidgets('a guest who taps scan is taken to sign-in', (tester) async {
    await pumpApp(tester);
    await tester.tap(find.text('تصفّح المتجر كزائر'));
    await tester.pumpAndSettle();

    await _openScanner(tester);

    expect(find.text('رقم الجوال'), findsOneWidget);
  });
}
