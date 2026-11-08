import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

import 'app_localizations.dart';
import 'app_localizations_override.dart';

/// Loads the app's wording with the texts changed from the dashboard laid over it.
///
/// [texts] maps a language code to `{key: text}`. A new map reloads every screen's wording.
class AppTextsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const AppTextsDelegate(this.texts);

  final Map<String, Map<String, String>> texts;

  @override
  bool isSupported(Locale locale) => AppLocalizations.delegate.isSupported(locale);

  @override
  Future<AppLocalizations> load(Locale locale) async {
    final builtIn = await AppLocalizations.delegate.load(locale);
    final changed = texts[locale.languageCode];
    return changed == null || changed.isEmpty ? builtIn : OverriddenLocalizations(builtIn, changed);
  }

  @override
  bool shouldReload(AppTextsDelegate old) => !mapEquals(old.texts, texts);

  /// Every delegate the app needs, with this one in place of the plain [AppLocalizations.delegate].
  static List<LocalizationsDelegate<dynamic>> all(Map<String, Map<String, String>> texts) => [
        AppTextsDelegate(texts),
        ...AppLocalizations.localizationsDelegates.where((d) => d != AppLocalizations.delegate),
      ];
}
