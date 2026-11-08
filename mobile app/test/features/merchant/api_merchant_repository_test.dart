import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/merchant/data/api_merchant_repository.dart';
import 'package:maqam_app/features/merchant/domain/merchant.dart';

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

ApiMerchantRepository _repository(MockClientHandler handler) =>
    ApiMerchantRepository(ApiClient(httpClient: MockClient(handler), token: () => 't'));

const _profile = {
  'id': 4,
  'merchant_code': 'M-AB12CD34',
  'business_name': 'معرض النور',
  'business_address': null,
  'logo_url': null,
  'is_approved': true,
  'approved_at': '2026-10-01T10:00:00+00:00',
};

void main() {
  test('maps the profile and its statistics', () async {
    final repository = _repository((_) async => _json({
          'success': true,
          'message': '',
          'data': {
            ..._profile,
            'stats': {'total_scans': 120, 'total_points': 600, 'scans_this_month': 18, 'unique_customers': 37},
          },
        }));

    final profile = await repository.profile();

    expect(profile?.code, 'M-AB12CD34');
    expect(profile?.approved, isTrue);
    expect(profile?.stats.totalScans, 120);
    expect(profile?.stats.uniqueCustomers, 37);
  });

  test('no profile yet is null, not an error', () async {
    final repository = _repository((_) async => _json({
          'success': false,
          'message': 'غير موجود.',
          'error_code': 'NOT_FOUND',
        }, 404));

    expect(await repository.profile(), isNull);
  });

  test('applies with a logo as a multipart form', () async {
    late http.BaseRequest sent;
    late String body;
    final repository = _repository((request) async {
      sent = request;
      body = utf8.decode(request.bodyBytes, allowMalformed: true);
      return _json({'success': true, 'message': '', 'data': {..._profile, 'is_approved': false}}, 201);
    });

    final profile = await repository.apply(
      businessName: 'معرض النور',
      businessAddress: 'القاهرة',
      logo: const LogoImage(bytes: [137, 80, 78, 71], fileName: 'logo.png'),
    );

    expect(sent.url.path, endsWith('/merchant/apply'));
    expect(sent.headers['Content-Type'], startsWith('multipart/form-data'));
    expect(sent.headers['Authorization'], 'Bearer t');
    expect(body, contains('name="business_name"'));
    expect(body, contains('معرض النور'));
    expect(body, contains('name="logo"; filename="logo.png"'));
    expect(profile.approved, isFalse);
  });

  test('applying twice is refused', () async {
    final repository = _repository((_) async => _json({
          'success': false,
          'message': 'يوجد طلب مسبق.',
          'error_code': 'MERCHANT_ALREADY_EXISTS',
        }, 409));

    await expectLater(
      repository.apply(businessName: 'معرض'),
      throwsA(isA<ApiException>().having((e) => e.code, 'code', 'MERCHANT_ALREADY_EXISTS')),
    );
  });
}
