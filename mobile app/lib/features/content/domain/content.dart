/// The legal pages the app links to.
enum LegalPageSlug {
  terms('terms'),
  privacy('privacy');

  const LegalPageSlug(this.apiValue);

  final String apiValue;
}

enum LegalBlockKind { heading, paragraph, bullet }

/// One heading, paragraph or list item of a legal page.
class LegalBlock {
  const LegalBlock(this.kind, this.text);

  final LegalBlockKind kind;
  final String text;
}

class LegalPage {
  const LegalPage({required this.title, required this.lead, required this.blocks});

  final String title;
  final String lead;
  final List<LegalBlock> blocks;
}

/// Whether this build of the app may still be used.
class AppUpdateStatus {
  const AppUpdateStatus({
    required this.required,
    required this.currentVersion,
    required this.minimumVersion,
    this.storeUrl,
  });

  /// True when this version is below the minimum: the app stops until updated.
  final bool required;
  final String currentVersion;
  final String minimumVersion;
  final Uri? storeUrl;
}

/// One message in a conversation with the support assistant.
class ChatTurn {
  const ChatTurn({required this.fromCustomer, required this.text});

  final bool fromCustomer;
  final String text;
}

class ChatReply {
  const ChatReply({required this.text, this.messagesLeftToday});

  final String text;
  final int? messagesLeftToday;
}

abstract interface class ContentRepository {
  Future<LegalPage> legalPage(LegalPageSlug slug);

  /// Asks whether [version] of the app on [platform] (`android` or `ios`) may run.
  Future<AppUpdateStatus> checkForUpdate({required String platform, required String version});

  /// The store's WhatsApp number in international form (`2010…`), when set.
  Future<String?> supportWhatsapp();

  /// Wording changed from the dashboard, as `{languageCode: {key: text}}`; empty when nothing changed.
  Future<Map<String, Map<String, String>>> appTexts();

  /// One turn with the assistant. [history] is the conversation so far, oldest first.
  Future<ChatReply> ask(String message, {List<ChatTurn> history = const []});
}
