import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/content.dart';

class ApiContentRepository implements ContentRepository {
  const ApiContentRepository(this._api);

  final ApiClient _api;

  /// The server keeps only the last turns of a conversation.
  static const _historyLimit = 10;

  @override
  Future<LegalPage> legalPage(LegalPageSlug slug) async {
    final data = await _api.get('/content/pages/${slug.apiValue}') as Map<String, dynamic>;
    return LegalPage(
      title: plainText(data['title'] as String? ?? ''),
      lead: plainText(data['lead'] as String? ?? ''),
      blocks: legalBlocks(data['html'] as String? ?? ''),
    );
  }

  @override
  Future<AppUpdateStatus> checkForUpdate({required String platform, required String version}) async {
    final data = await _api.get('/app/config', query: {'platform': platform, 'version': version})
        as Map<String, dynamic>;
    final storeUrl = data['store_url'] as String?;
    return AppUpdateStatus(
      required: data['update_required'] == true,
      currentVersion: version,
      minimumVersion: data['minimum_version'] as String? ?? version,
      storeUrl: storeUrl == null || storeUrl.isEmpty ? null : Uri.tryParse(storeUrl),
    );
  }

  @override
  Future<String?> supportWhatsapp() async {
    final data = await _api.get('/settings');
    if (data is! Map<String, dynamic>) return null;
    final digits = '${data['store_whatsapp'] ?? ''}'.replaceAll(RegExp(r'\D'), '');
    return digits.isEmpty ? null : digits;
  }

  @override
  Future<Map<String, Map<String, String>>> appTexts() async => parseAppTexts(await _api.get('/app/texts'));

  @override
  Future<ChatReply> ask(String message, {List<ChatTurn> history = const []}) async {
    final recent = history.length > _historyLimit
        ? history.sublist(history.length - _historyLimit)
        : history;
    final data = await _api.post('/chat', body: {
      'message': message,
      'history': [
        for (final turn in recent) {'role': turn.fromCustomer ? 'user' : 'assistant', 'text': turn.text},
      ],
    }) as Map<String, dynamic>;
    return ChatReply(
      text: (data['reply'] as String? ?? '').trim(),
      messagesLeftToday: data['messages_left_today'] as int?,
    );
  }

  /// Splits the page's simple HTML (headings, paragraphs, list items) into blocks.
  static List<LegalBlock> legalBlocks(String html) {
    final element = RegExp(r'<(h[1-6]|p|li)\b[^>]*>(.*?)</\1>', dotAll: true, caseSensitive: false);
    final blocks = [
      for (final match in element.allMatches(html))
        if (plainText(match[2]!).isNotEmpty)
          LegalBlock(
            switch (match[1]!.toLowerCase()) {
              'p' => LegalBlockKind.paragraph,
              'li' => LegalBlockKind.bullet,
              _ => LegalBlockKind.heading,
            },
            plainText(match[2]!),
          ),
    ];
    if (blocks.isEmpty && plainText(html).isNotEmpty) {
      return [LegalBlock(LegalBlockKind.paragraph, plainText(html))];
    }
    return blocks;
  }

  /// Text without tags or entities, on one line, with Western digits.
  static String plainText(String html) => html
      .replaceAll(RegExp('<[^>]+>'), ' ')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .replaceAll('&lt;', '<')
      .replaceAll('&gt;', '>')
      .replaceAll('&amp;', '&')
      .replaceAllMapped(
        RegExp('[٠-٩]'),
        (match) => String.fromCharCode(match[0]!.codeUnitAt(0) - 0x0660 + 0x30),
      )
      .replaceAll(RegExp(r'\s+'), ' ')
      .trim();
}

/// Reads `{texts: {ar: {...}, en: {...}}}`; an empty language may arrive as a list.
Map<String, Map<String, String>> parseAppTexts(Object? data) {
  final texts = data is Map<String, dynamic> ? data['texts'] : null;
  if (texts is! Map<String, dynamic>) return const {};
  return {
    for (final MapEntry(:key, :value) in texts.entries)
      if (value is Map<String, dynamic>)
        key: {
          for (final entry in value.entries)
            if (entry.value is String) entry.key: entry.value as String,
        },
  };
}

final contentRepositoryProvider = Provider<ContentRepository>(
  (ref) => ApiContentRepository(ref.watch(apiClientProvider)),
);
