import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;

import '../locale/locale_provider.dart';
import '../session/account_status.dart';
import '../session/session_provider.dart';
import 'api_exception.dart';

const String defaultApiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://maqam-eg.com/api/v1',
);

/// One page of a paginated list, with the total across all pages.
class ApiPage {
  const ApiPage({required this.items, required this.total, this.meta = const {}});

  final List<Map<String, dynamic>> items;
  final int total;

  /// The rest of the envelope's `meta`, for endpoints that add their own counts.
  final Map<String, dynamic> meta;
}

/// Talks to the MAQAM API and unwraps its `{success, message, data}` envelope.
class ApiClient {
  ApiClient({
    http.Client? httpClient,
    this.baseUrl = defaultApiBaseUrl,
    String Function()? locale,
    String? Function()? token,
    this.onUnauthorized,
    this.onAccountFrozen,
  })  : _http = httpClient ?? http.Client(),
        _locale = locale ?? (() => 'ar'),
        _token = token ?? (() => null);

  final http.Client _http;
  final String baseUrl;
  final String Function() _locale;
  final String? Function() _token;

  /// Called when the server rejects the token that was sent.
  final void Function()? onUnauthorized;

  /// Called when the server says the account is frozen for review.
  final void Function()? onAccountFrozen;

  /// Returns the envelope's `data`.
  Future<dynamic> get(String path, {Map<String, String>? query}) async {
    return (await _getEnvelope(path, query))['data'];
  }

  /// Returns a paginated endpoint's `data` together with `meta.total`.
  Future<ApiPage> getPage(String path, {Map<String, String>? query}) async {
    final body = await _getEnvelope(path, query);
    final items = (body['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    final meta = body['meta'] as Map<String, dynamic>?;
    return ApiPage(
      items: items,
      total: meta?['total'] as int? ?? items.length,
      meta: meta ?? const {},
    );
  }

  Future<Map<String, dynamic>> _getEnvelope(String path, Map<String, String>? query) {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    return _send((headers) => _http.get(uri, headers: headers));
  }

  /// Returns the envelope's `data`.
  Future<dynamic> post(String path, {Map<String, Object?> body = const {}}) =>
      _sendJson(_http.post, path, body);

  /// Returns the envelope's `data`.
  Future<dynamic> put(String path, {Map<String, Object?> body = const {}}) =>
      _sendJson(_http.put, path, body);

  /// Returns the envelope's `data`.
  Future<dynamic> delete(String path, {Map<String, Object?> body = const {}}) =>
      _sendJson(_http.delete, path, body);

  /// Sends [fields] as a multipart form, with one file under [fileField] when
  /// [fileBytes] are given. Returns the envelope's `data`.
  Future<dynamic> postMultipart(
    String path, {
    Map<String, String> fields = const {},
    String fileField = 'file',
    List<int>? fileBytes,
    String? fileName,
  }) async {
    final uri = Uri.parse('$baseUrl$path');
    final envelope = await _send((headers) async {
      final request = http.MultipartRequest('POST', uri)
        ..headers.addAll(headers)
        ..fields.addAll(fields);
      if (fileBytes != null) {
        request.files.add(http.MultipartFile.fromBytes(fileField, fileBytes, filename: fileName));
      }
      return http.Response.fromStream(await _http.send(request));
    });
    return envelope['data'];
  }

  Future<dynamic> _sendJson(
    Future<http.Response> Function(Uri, {Map<String, String>? headers, Object? body}) method,
    String path,
    Map<String, Object?> body,
  ) async {
    final uri = Uri.parse('$baseUrl$path');
    final envelope = await _send(
      (headers) => method(
        uri,
        headers: {...headers, 'Content-Type': 'application/json'},
        body: jsonEncode(body),
      ),
    );
    return envelope['data'];
  }

  Future<Map<String, dynamic>> _send(Future<http.Response> Function(Map<String, String> headers) request) async {
    final token = _token();
    final http.Response response;
    try {
      response = await request({
        'Accept': 'application/json',
        'X-App-Locale': _locale(),
        if (token != null) 'Authorization': 'Bearer $token',
      });
    } on Exception {
      // No connection, DNS failure, TLS failure, timeout.
      throw const ApiException(ApiErrorKind.network);
    }
    if (response.statusCode == 401 && token != null) {
      onUnauthorized?.call();
    }
    try {
      return _unwrap(response);
    } on ApiException catch (e) {
      if (e.code == ApiErrorCodes.accountFrozen) onAccountFrozen?.call();
      rethrow;
    }
  }

  Map<String, dynamic> _unwrap(http.Response response) {
    Map<String, dynamic>? body;
    try {
      body = jsonDecode(utf8.decode(response.bodyBytes)) as Map<String, dynamic>?;
    } on Exception {
      // Not JSON (for example an HTML error page from the host).
      body = null;
    }
    final ok = response.statusCode >= 200 && response.statusCode < 300;
    if (ok && body != null && body['success'] == true) {
      return body;
    }
    throw ApiException(
      switch (response.statusCode) {
        401 => ApiErrorKind.unauthorized,
        422 => ApiErrorKind.validation,
        _ => ApiErrorKind.server,
      },
      message: body?['message'] as String?,
      code: body?['error_code'] as String?,
    );
  }
}

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(
    locale: () => ref.read(localeProvider).languageCode,
    token: () => ref.read(sessionProvider)?.token,
    onUnauthorized: () => ref.read(sessionProvider.notifier).end(),
    onAccountFrozen: () => ref.read(accountFrozenProvider.notifier).freeze(),
  );
});
