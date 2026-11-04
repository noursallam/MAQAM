import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/locale_provider.dart';
import '../../core/network/api_exception.dart';
import '../../core/storage/key_value_store.dart';
import '../../core/widgets/page_widgets.dart';
import 'data/api_content_repository.dart';
import 'domain/content.dart';

final legalPageProvider =
    FutureProvider.autoDispose.family<LegalPage, LegalPageSlug>((ref, slug) {
  ref.watch(localeProvider);
  return ref.watch(contentRepositoryProvider).legalPage(slug);
});

final supportWhatsappProvider = FutureProvider.autoDispose<String?>(
  (ref) => ref.watch(contentRepositoryProvider).supportWhatsapp(),
);

/// Wording changed from the dashboard, as `{languageCode: {key: text}}`.
///
/// The last copy is kept on the device, so the changed wording shows at launch and offline;
/// [refresh] then asks the server for the current one.
final appTextsProvider =
    NotifierProvider<AppTextsNotifier, Map<String, Map<String, String>>>(AppTextsNotifier.new);

class AppTextsNotifier extends Notifier<Map<String, Map<String, String>>> {
  static const _storeKey = 'app_texts';

  @override
  Map<String, Map<String, String>> build() => const {};

  Future<void> refresh() async {
    final store = ref.read(keyValueStoreProvider);
    if (state.isEmpty) {
      final saved = await store.read(_storeKey);
      if (saved != null) state = parseAppTexts(jsonDecode(saved));
    }
    try {
      final texts = await ref.read(contentRepositoryProvider).appTexts();
      state = texts;
      await store.write(_storeKey, jsonEncode({'texts': texts}));
    } on ApiException {
      // Offline: the saved copy (or the built-in wording) stays.
    }
  }
}

/// What the server said about this version at launch; null until it has answered.
final appUpdateProvider = NotifierProvider<AppUpdateNotifier, AppUpdateStatus?>(AppUpdateNotifier.new);

class AppUpdateNotifier extends Notifier<AppUpdateStatus?> {
  @override
  AppUpdateStatus? build() => null;

  Future<void> check() async {
    // Only the store builds are versioned on the server.
    final platform = switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      _ => null,
    };
    if (kIsWeb || platform == null) return;
    try {
      state = await ref
          .read(contentRepositoryProvider)
          .checkForUpdate(platform: platform, version: appVersion);
    } on ApiException {
      // Offline or the server is busy: let the customer in; the next launch asks again.
    }
  }
}
