import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/session/auth_session.dart';
import '../../../core/session/session_provider.dart';
import '../domain/auth_repository.dart';
import '../domain/whatsapp_challenge.dart';
import 'api_auth_repository.dart';

/// Proves a new phone number for the signed-in account with the same
/// WhatsApp steps as sign-in, so the sign-in screens can drive it. The
/// session stays the same; "signing in" here hands back the session with the
/// new number.
class PhoneChangeRepository implements AuthRepository {
  const PhoneChangeRepository(this._api, this._session);

  final ApiClient _api;
  final AuthSession? _session;

  @override
  Future<WhatsappChallenge> startChallenge(String phone) async =>
      ApiAuthRepository.challengeFromJson(
        await _api.post('/user/phone/start', body: {'phone': phone}) as Map<String, dynamic>,
      );

  @override
  Future<Duration?> checkChallenge(String challengeId) async =>
      ApiAuthRepository.otpValidityFromJson(
        await _api.post('/auth/whatsapp/check', body: {'challenge_id': challengeId})
            as Map<String, dynamic>,
      );

  @override
  Future<SignIn> verifyOtp({required String challengeId, required String otp}) async {
    final data = await _api.post('/user/phone/verify', body: {
      'challenge_id': challengeId,
      'otp': otp,
    }) as Map<String, dynamic>;
    final session = _session;
    if (session == null) throw StateError('Changing the phone number needs a session');
    return SignIn(
      session: AuthSession(
        token: session.token,
        expiresAt: session.expiresAt,
        fullName: session.fullName,
        phone: data['phone_number'] as String? ?? session.phone,
      ),
      hasPassword: true,
    );
  }

  // The steps below belong to signing in, not to changing the number.

  @override
  Future<bool> hasPassword(String phone) => throw UnsupportedError('hasPassword');

  @override
  Future<SignIn> signInWithPassword({required String phone, required String password}) =>
      throw UnsupportedError('signInWithPassword');

  @override
  Future<void> completeProfile({required String fullName, required String password, String? governorate, String? area}) =>
      throw UnsupportedError('completeProfile');

  @override
  Future<void> setPassword(String password) => throw UnsupportedError('setPassword');

  @override
  Future<void> signOut({String? pushToken}) => throw UnsupportedError('signOut');

  @override
  Future<String> enableQuickSignIn() => throw UnsupportedError('enableQuickSignIn');

  @override
  Future<SignIn> quickSignIn(String secret) => throw UnsupportedError('quickSignIn');

  @override
  Future<void> disableQuickSignIn() => throw UnsupportedError('disableQuickSignIn');
}

final phoneChangeRepositoryProvider = Provider<AuthRepository>(
  (ref) => PhoneChangeRepository(ref.watch(apiClientProvider), ref.watch(sessionProvider)),
);
