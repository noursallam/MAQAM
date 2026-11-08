import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/account/data/api_account_repository.dart';
import 'package:maqam_app/features/addresses/data/api_address_book_repository.dart';
import 'package:maqam_app/features/addresses/domain/address_book.dart';
import 'package:maqam_app/features/auth/data/phone_change_repository.dart';

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

const _ok = {'success': true, 'message': '', 'data': null};

ApiClient _client(MockClientHandler handler) => ApiClient(httpClient: MockClient(handler), token: () => 't');

const _draft = AddressDraft(
  recipientName: 'أحمد محمود',
  phone: '01012345678',
  governorate: 'الجيزة',
  city: 'المهندسين',
  street: 'شارع جامعة الدول 5',
);

void main() {
  group('account', () {
    test('saves the name and deletes the account with the confirmation word', () async {
      final sent = <http.Request>[];
      final repository = ApiAccountRepository(_client((request) async {
        sent.add(request);
        return _json(_ok);
      }));

      await repository.updateName('أحمد علي');
      await repository.deleteAccount();

      expect(sent[0].method, 'PUT');
      expect(sent[0].url.path, endsWith('/user/profile'));
      expect(jsonDecode(sent[0].body), {'full_name': 'أحمد علي'});
      expect(sent[1].method, 'DELETE');
      expect(sent[1].url.path, endsWith('/user/account'));
      expect(jsonDecode(sent[1].body), {'confirm': 'DELETE'});
    });

    test('a refused deletion throws with the server message', () async {
      final repository = ApiAccountRepository(_client((_) async => _json({
            'success': false,
            'message': 'بيانات غير صالحة.',
            'error_code': 'VALIDATION_ERROR',
          }, 422)));

      await expectLater(
        repository.deleteAccount(),
        throwsA(isA<ApiException>().having((e) => e.kind, 'kind', ApiErrorKind.validation)),
      );
    });
  });

  group('address book', () {
    test('maps saved addresses', () async {
      final repository = ApiAddressBookRepository(_client((_) async => _json({
            'success': true,
            'message': '',
            'data': [
              {
                'id': 3,
                'recipient_name': 'أحمد',
                'phone': '01012345678',
                'governorate': 'القاهرة',
                'city': 'مدينة نصر',
                'address': 'شارع الطيران',
                'notes': null,
                'is_default': true,
              },
            ],
          })));

      final list = await repository.addresses();

      expect(list.single.id, 3);
      expect(list.single.street, 'شارع الطيران');
      expect(list.single.isDefault, isTrue);
    });

    test('adds, replaces as default and deletes', () async {
      final sent = <http.Request>[];
      final repository = ApiAddressBookRepository(_client((request) async {
        sent.add(request);
        return _json(_ok);
      }));

      await repository.add(_draft);
      await repository.update(4, _draft, makeDefault: true);
      await repository.delete(4);

      expect(sent.map((r) => '${r.method} ${r.url.path.split('/v1').last}'),
          ['POST /addresses', 'PUT /addresses/4', 'DELETE /addresses/4']);
      expect(jsonDecode(sent[0].body), {
        'recipient_name': 'أحمد محمود',
        'phone': '01012345678',
        'governorate': 'الجيزة',
        'city': 'المهندسين',
        'address': 'شارع جامعة الدول 5',
      });
      expect((jsonDecode(sent[1].body) as Map)['is_default'], isTrue);
    });

    test('a full address book is refused', () async {
      final repository = ApiAddressBookRepository(_client((_) async => _json({
            'success': false,
            'message': 'دفتر العناوين ممتلئ.',
            'error_code': 'ADDRESS_LIMIT_REACHED',
          }, 422)));

      await expectLater(
        repository.add(_draft),
        throwsA(isA<ApiException>().having((e) => e.code, 'code', 'ADDRESS_LIMIT_REACHED')),
      );
    });

    test('a dropped connection is a network error', () async {
      final repository = ApiAddressBookRepository(
        _client((_) async => throw http.ClientException('offline')),
      );

      await expectLater(
        repository.addresses(),
        throwsA(isA<ApiException>().having((e) => e.kind, 'kind', ApiErrorKind.network)),
      );
    });
  });

  group('phone change', () {
    test('starts from the new number and keeps the session with that number', () async {
      final sent = <http.Request>[];
      final repository = PhoneChangeRepository(
        _client((request) async {
          sent.add(request);
          return _json({
            'success': true,
            'message': '',
            'data': switch (request.url.path.split('/v1').last) {
              '/user/phone/start' => {
                  'challenge_id': 'c1',
                  'code': 'MAQAM-111222',
                  'whatsapp_url': 'https://wa.me/201000000000?text=MAQAM-111222',
                  'expires_in_seconds': 600,
                  'poll_interval_seconds': 4,
                },
              '/auth/whatsapp/check' => {'otp_sent': true, 'otp_expires_in_seconds': 300},
              _ => {'phone_number': '01212345678'},
            },
          });
        }),
        const AuthSession(token: 'saved', fullName: 'أحمد علي', phone: '01012345678'),
      );

      final challenge = await repository.startChallenge('01212345678');
      final validFor = await repository.checkChallenge(challenge.id);
      final signIn = await repository.verifyOtp(challengeId: challenge.id, otp: '654321');

      expect(sent.map((r) => r.url.path.split('/v1').last),
          ['/user/phone/start', '/auth/whatsapp/check', '/user/phone/verify']);
      expect(jsonDecode(sent.first.body), {'phone': '01212345678'});
      expect(challenge.code, 'MAQAM-111222');
      expect(validFor, const Duration(minutes: 5));
      expect(signIn.session.token, 'saved');
      expect(signIn.session.fullName, 'أحمد علي');
      expect(signIn.session.phone, '01212345678');
    });

    test('a number already in use is refused', () async {
      final repository = PhoneChangeRepository(
        _client((_) async => _json({
              'success': false,
              'message': 'الرقم مستخدم.',
              'error_code': 'PHONE_TAKEN',
            }, 422)),
        const AuthSession(token: 'saved'),
      );

      await expectLater(
        repository.startChallenge('01212345678'),
        throwsA(isA<ApiException>().having((e) => e.code, 'code', 'PHONE_TAKEN')),
      );
    });
  });
}
