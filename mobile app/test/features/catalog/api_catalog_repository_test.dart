import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/catalog/data/api_catalog_repository.dart';

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

ApiCatalogRepository _repository(MockClientHandler handler) =>
    ApiCatalogRepository(ApiClient(httpClient: MockClient(handler), locale: () => 'en'));

Matcher _apiError(ApiErrorKind kind) =>
    throwsA(isA<ApiException>().having((e) => e.kind, 'kind', kind));

void main() {
  test('maps products and sends the category filter and locale', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({
        'success': true,
        'message': '',
        'data': [
          {
            'id': 1,
            'name': 'Switch',
            'price': '80.00',
            'min_price': '75.00',
            'image_url': 'https://example.test/a.jpg',
            'category': {'id': 7, 'name': 'Switches'},
          },
          {
            'id': 2,
            'name': 'Sensor',
            'price': '0.00',
            'stock_quantity': 3,
            'image_url': null,
            'category': null,
          },
        ],
        'meta': {'current_page': 1, 'last_page': 4, 'per_page': 30, 'total': 113},
      });
    });

    final page = await repository.products(categoryId: 7, search: 'sw');
    final products = page.items;

    expect(sent.url.path, endsWith('/store/products'));
    expect(sent.url.queryParameters['category_id'], '7');
    expect(sent.url.queryParameters['search'], 'sw');
    expect(sent.url.queryParameters['sort'], 'newest');
    expect(page.total, 113);
    expect(products.first.inStock, isFalse);
    expect(products.last.inStock, isTrue);
    expect(sent.headers['X-App-Locale'], 'en');
    expect(products.first.price, 75);
    expect(products.first.categoryName, 'Switches');
    expect(products.last.hasPrice, isFalse);
  });

  test('throws a server error when the API reports failure', () async {
    final repository = _repository(
      (_) async => _json({'success': false, 'message': 'nope', 'error_code': 'X'}, 500),
    );

    await expectLater(repository.categories(), _apiError(ApiErrorKind.server));
  });

  test('throws a network error when the request cannot be sent', () async {
    final repository = _repository((_) async => throw http.ClientException('offline'));

    await expectLater(repository.products(), _apiError(ApiErrorKind.network));
  });
}
