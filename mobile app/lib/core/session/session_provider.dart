import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/key_value_store.dart';
import 'auth_session.dart';

const _sessionKey = 'session';

/// Reads the saved session at startup. Returns null when there is none, it has
/// expired, or it cannot be read.
Future<AuthSession?> readStoredSession(KeyValueStore store, {DateTime? now}) async {
  try {
    final raw = await store.read(_sessionKey);
    if (raw == null) return null;
    final json = jsonDecode(raw) as Map<String, dynamic>;
    final expiresAt = json['expires_at'] as String?;
    final session = AuthSession(
      token: json['token'] as String,
      expiresAt: expiresAt == null ? null : DateTime.parse(expiresAt),
      fullName: json['full_name'] as String?,
      phone: json['phone'] as String?,
      boundMerchantId: json['bound_merchant_id'] as int?,
      boundMerchantName: json['bound_merchant_name'] as String?,
      governorate: json['governorate'] as String?,
      area: json['area'] as String?,
    );
    return session.isExpiredAt(now ?? DateTime.now()) ? null : session;
  } on Exception {
    // Unreadable storage must not block startup; the customer signs in again.
    return null;
  }
}

/// Set by `main` to the session restored before the first frame.
final initialSessionProvider = Provider<AuthSession?>((ref) => null);

/// The current session, or null for a guest.
final sessionProvider = NotifierProvider<SessionNotifier, AuthSession?>(SessionNotifier.new);

class SessionNotifier extends Notifier<AuthSession?> {
  @override
  AuthSession? build() => ref.read(initialSessionProvider);

  Future<void> start(AuthSession session) async {
    await ref.read(keyValueStoreProvider).write(
          _sessionKey,
          jsonEncode({
            'token': session.token,
            'expires_at': session.expiresAt?.toIso8601String(),
            'full_name': session.fullName,
            'phone': session.phone,
            'bound_merchant_id': session.boundMerchantId,
            'bound_merchant_name': session.boundMerchantName,
            'governorate': session.governorate,
            'area': session.area,
          }),
        );
    state = session;
  }

  Future<void> end() async {
    state = null;
    await ref.read(keyValueStoreProvider).delete(_sessionKey);
  }
}
