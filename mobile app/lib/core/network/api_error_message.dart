import 'package:flutter/widgets.dart';

import '../../l10n/l10n.dart';
import 'api_exception.dart';

/// The message to show a customer for a failed request: the server's own
/// localized text when it sent one, otherwise a generic one.
String apiErrorMessage(BuildContext context, ApiException error) {
  final l10n = context.l10n;
  if (error.kind == ApiErrorKind.network) return l10n.errorNetwork;
  final message = error.message;
  return message == null || message.isEmpty ? l10n.errorGeneric : message;
}
