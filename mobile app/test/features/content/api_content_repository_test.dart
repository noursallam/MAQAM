import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/content/data/api_content_repository.dart';
import 'package:maqam_app/features/content/domain/content.dart';

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

ApiContentRepository _repository(MockClientHandler handler) =>
    ApiContentRepository(ApiClient(httpClient: MockClient(handler), token: () => 't'));

void main() {
  test('splits a legal page into headings, paragraphs and bullets with Western digits', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({
        'success': true,
        'message': '',
        'data': {
          'slug': 'terms',
          'title': 'الشروط والأحكام',
          'lead': 'آخر تحديث',
          'html': '<p>مقدمة &amp; ترحيب</p>\n<h3>مدة التوصيل</h3>\n<ul><li>خلال ١–٣ أيام</li><li> <b>عادة</b> </li></ul>',
          'url': 'https://maqam-eg.com/terms',
        },
      });
    });

    final page = await repository.legalPage(LegalPageSlug.terms);

    expect(sent.url.path, endsWith('/content/pages/terms'));
    expect(page.title, 'الشروط والأحكام');
    expect(page.blocks.map((b) => b.kind), [
      LegalBlockKind.paragraph,
      LegalBlockKind.heading,
      LegalBlockKind.bullet,
      LegalBlockKind.bullet,
    ]);
    expect(page.blocks.first.text, 'مقدمة & ترحيب');
    expect(page.blocks[2].text, 'خلال 1–3 أيام');
    expect(page.blocks.last.text, 'عادة');
  });

  test('asks whether this version may run', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({
        'success': true,
        'message': '',
        'data': {
          'update_required': true,
          'update_available': true,
          'minimum_version': '1.2.0',
          'latest_version': '1.5.0',
          'store_url': 'https://play.google.com/store/apps/details?id=maqam.egypt',
        },
      });
    });

    final status = await repository.checkForUpdate(platform: 'android', version: '1.0.0');

    expect(sent.url.queryParameters, {'platform': 'android', 'version': '1.0.0'});
    expect(status.required, isTrue);
    expect(status.minimumVersion, '1.2.0');
    expect(status.storeUrl?.host, 'play.google.com');
  });

  test('sends the last ten turns with a question to the assistant', () async {
    late Map<String, dynamic> body;
    final repository = _repository((request) async {
      body = jsonDecode(request.body) as Map<String, dynamic>;
      return _json({
        'success': true,
        'message': '',
        'data': {'reply': ' الشحن 35 جنيهاً. ', 'messages_left_today': 57},
      });
    });
    final history = [
      for (var i = 0; i < 12; i++) ChatTurn(fromCustomer: i.isEven, text: 'رسالة $i'),
    ];

    final reply = await repository.ask('كم الشحن؟', history: history);

    expect(body['message'], 'كم الشحن؟');
    expect(body['history'], hasLength(10));
    expect((body['history'] as List).first, {'role': 'user', 'text': 'رسالة 2'});
    expect((body['history'] as List).last, {'role': 'assistant', 'text': 'رسالة 11'});
    expect(reply.text, 'الشحن 35 جنيهاً.');
    expect(reply.messagesLeftToday, 57);
  });

  test('reads the support WhatsApp number from the public settings', () async {
    final repository = _repository((_) async => _json({
          'success': true,
          'message': '',
          'data': {'store_whatsapp': '+20 100 123 4567', 'shipping_cost': '35.00'},
        }));

    expect(await repository.supportWhatsapp(), '201001234567');
  });

  test('passes on the server refusing a question', () async {
    final repository = _repository((_) async => _json({
          'success': false,
          'message': 'انتهى الحد اليومي.',
          'error_code': 'CHAT_LIMIT_REACHED',
        }, 429));

    await expectLater(
      repository.ask('مرحبا'),
      throwsA(isA<ApiException>()
          .having((e) => e.code, 'code', 'CHAT_LIMIT_REACHED')
          .having((e) => e.message, 'message', 'انتهى الحد اليومي.')),
    );
  });

  test('a page that is not JSON is a server error', () async {
    final repository = _repository((_) async => http.Response('<html>502</html>', 502));

    await expectLater(
      repository.legalPage(LegalPageSlug.privacy),
      throwsA(isA<ApiException>().having((e) => e.kind, 'kind', ApiErrorKind.server)),
    );
  });

  test('a frozen account is reported to the app', () async {
    var frozen = false;
    final repository = ApiContentRepository(ApiClient(
      httpClient: MockClient((_) async => _json({
            'success': false,
            'message': 'الحساب موقوف.',
            'error_code': 'ACCOUNT_FROZEN_FRAUD',
          }, 403)),
      token: () => 't',
      onAccountFrozen: () => frozen = true,
    ));

    await expectLater(repository.ask('مرحبا'), throwsA(isA<ApiException>()));
    expect(frozen, isTrue);
  });
}
