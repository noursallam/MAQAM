import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/core/storage/key_value_store.dart';
import 'package:maqam_app/features/auth/data/api_auth_repository.dart';

http.Response _json(Object? data, {int status = 200, bool success = true, String? code}) =>
    http.Response.bytes(
      utf8.encode(jsonEncode({
        'success': success,
        'message': success ? '' : 'رسالة من الخادم',
        'data': data,
        'error_code': ?code,
      })),
      status,
    );

void main() {
  test('starts a challenge with the normalized phone number', () async {
    late http.Request sent;
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          sent = request;
          return _json({
            'challenge_id': 'abc',
            'code': 'MAQAM-482913',
            'whatsapp_number': '201000000000',
            'whatsapp_url': 'https://wa.me/201000000000?text=MAQAM-482913',
            'expires_in_seconds': 600,
            'poll_interval_seconds': 4,
          });
        }),
      ),
      InMemoryKeyValueStore(),
    );

    final challenge = await repository.startChallenge('01012345678');

    expect(sent.method, 'POST');
    expect(sent.url.path, endsWith('/auth/whatsapp/start'));
    expect(jsonDecode(sent.body), {'phone': '01012345678'});
    expect(challenge.code, 'MAQAM-482913');
    expect(challenge.validFor, const Duration(minutes: 10));
    expect(challenge.pollInterval, const Duration(seconds: 4));
    expect(challenge.whatsappUrl.host, 'wa.me');
  });

  test('reports whether the sign-in code has been sent yet', () async {
    final answers = [
      {'otp_sent': false, 'otp_expires_in_seconds': null},
      {'otp_sent': true, 'otp_expires_in_seconds': 300},
    ];
    final repository = ApiAuthRepository(
      ApiClient(httpClient: MockClient((_) async => _json(answers.removeAt(0)))),
      InMemoryKeyValueStore(),
    );

    expect(await repository.checkChallenge('abc'), isNull);
    expect(await repository.checkChallenge('abc'), const Duration(minutes: 5));
  });

  test('verifies the code with a device name that is stable for this install', () async {
    final bodies = <Map<String, dynamic>>[];
    final store = InMemoryKeyValueStore();
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          bodies.add(jsonDecode(request.body) as Map<String, dynamic>);
          return _json({
            'token': '1|secret',
            'token_type': 'Bearer',
            'expires_at': '2027-01-06T00:00:00+00:00',
            'is_new_user': false,
            'user': {
              'id': 9,
              'full_name': 'عميل',
              'phone_number': '01012345678',
              'has_password': false,
            },
            'customer': null,
          });
        }),
      ),
      store,
    );

    final signIn = await repository.verifyOtp(challengeId: 'abc', otp: '123456');
    final session = signIn.session;
    await repository.verifyOtp(challengeId: 'abc', otp: '123456');

    expect(signIn.hasPassword, isFalse);
    expect(session.token, '1|secret');
    expect(session.fullName, 'عميل');
    expect(session.expiresAt, DateTime.utc(2027, 1, 6));
    expect(bodies.first['otp'], '123456');
    expect(bodies.first['device_name'], matches(RegExp(r'^[a-z]+-[0-9a-f]{16}$')));
    expect(bodies.last['device_name'], bodies.first['device_name']);
    expect(store.values['device_id'], hasLength(16));
  });

  test('surfaces the server error code and message for a wrong code', () async {
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient(
          (_) async => _json(null, status: 422, success: false, code: 'INVALID_OTP'),
        ),
      ),
      InMemoryKeyValueStore(),
    );

    await expectLater(
      repository.verifyOtp(challengeId: 'abc', otp: '000000'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.kind, 'kind', ApiErrorKind.validation)
            .having((e) => e.code, 'code', ApiErrorCodes.invalidOtp)
            .having((e) => e.message, 'message', 'رسالة من الخادم'),
      ),
    );
  });

  test('asks how a number signs in, then signs in with its password', () async {
    final requests = <http.Request>[];
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          requests.add(request);
          if (request.url.path.endsWith('/auth/methods')) return _json({'has_password': true});
          return _json({
            'token': '2|secret',
            'expires_at': null,
            'user': {'full_name': 'أحمد', 'phone_number': '01012345678', 'has_password': true},
          });
        }),
      ),
      InMemoryKeyValueStore(),
    );

    expect(await repository.hasPassword('01012345678'), isTrue);
    final signIn = await repository.signInWithPassword(phone: '01012345678', password: 'pass-1234');

    expect(jsonDecode(requests.first.body), {'phone': '01012345678'});
    final login = jsonDecode(requests.last.body) as Map<String, dynamic>;
    expect(requests.last.url.path, endsWith('/auth/login'));
    expect(login['password'], 'pass-1234');
    expect(login['device_name'], isNotEmpty);
    expect(signIn.session.token, '2|secret');
    expect(signIn.hasPassword, isTrue);
  });

  test('completes a profile and replaces a password', () async {
    final requests = <http.Request>[];
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          requests.add(request);
          return _json(null);
        }),
        token: () => 't',
      ),
      InMemoryKeyValueStore(),
    );

    await repository.completeProfile(fullName: 'أحمد محمود', password: 'pass-1234');
    await repository.setPassword('pass-5678');

    expect(requests.first.method, 'POST');
    expect(requests.first.url.path, endsWith('/user/complete-profile'));
    expect(jsonDecode(requests.first.body), {
      'full_name': 'أحمد محمود',
      'password': 'pass-1234',
      'password_confirmation': 'pass-1234',
    });
    expect(requests.last.method, 'PUT');
    expect(requests.last.url.path, endsWith('/user/password'));
    expect(jsonDecode(requests.last.body), {
      'password': 'pass-5678',
      'password_confirmation': 'pass-5678',
    });
  });

  test('sends the token and reports when the server rejects it', () async {
    var rejected = 0;
    late http.Request sent;
    final repository = ApiAuthRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          sent = request;
          return _json(null, status: 401, success: false);
        }),
        token: () => '1|secret',
        onUnauthorized: () => rejected++,
      ),
      InMemoryKeyValueStore(),
    );

    await expectLater(repository.signOut(), throwsA(isA<ApiException>()));

    expect(sent.headers['Authorization'], 'Bearer 1|secret');
    expect(rejected, 1);
  });
}
