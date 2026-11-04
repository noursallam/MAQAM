import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/key_value_store.dart';

const _key = 'quick_sign_in';

/// What lets this phone sign back in after the customer confirms themselves on
/// it (fingerprint, Face ID or the app PIN), instead of typing number and password.
class QuickSignInCredential {
  const QuickSignInCredential({required this.secret, this.phone, this.name});

  /// Issued by the server for this install; kept only in the encrypted store.
  final String secret;

  /// Whose account it opens, to greet them and to offer WhatsApp sign-in instead.
  final String? phone;
  final String? name;
}

Future<QuickSignInCredential?> readQuickSignIn(KeyValueStore store) async {
  try {
    final raw = await store.read(_key);
    if (raw == null) return null;
    final json = jsonDecode(raw) as Map<String, dynamic>;
    return QuickSignInCredential(
      secret: json['secret'] as String,
      phone: json['phone'] as String?,
      name: json['name'] as String?,
    );
  } on Exception {
    // Unreadable: the customer signs in the usual way and can turn it on again.
    return null;
  }
}

/// Set by `main` to the credential read before the first frame.
final initialQuickSignInProvider = Provider<QuickSignInCredential?>((ref) => null);

final quickSignInProvider =
    NotifierProvider<QuickSignInNotifier, QuickSignInCredential?>(QuickSignInNotifier.new);

class QuickSignInNotifier extends Notifier<QuickSignInCredential?> {
  @override
  QuickSignInCredential? build() => ref.read(initialQuickSignInProvider);

  Future<void> save(QuickSignInCredential credential) async {
    await ref.read(keyValueStoreProvider).write(
          _key,
          jsonEncode({'secret': credential.secret, 'phone': credential.phone, 'name': credential.name}),
        );
    state = credential;
  }

  Future<void> clear() async {
    state = null;
    await ref.read(keyValueStoreProvider).delete(_key);
  }
}
