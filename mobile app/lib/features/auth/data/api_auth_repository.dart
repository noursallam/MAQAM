import 'dart:math';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/session/auth_session.dart';
import '../../../core/storage/key_value_store.dart';
import '../domain/auth_repository.dart';
import '../domain/whatsapp_challenge.dart';

class ApiAuthRepository implements AuthRepository {
  const ApiAuthRepository(this._api, this._store);

  final ApiClient _api;
  final KeyValueStore _store;

  static const _deviceIdKey = 'device_id';

  @override
  Future<bool> hasPassword(String phone) async {
    final data = await _api.post('/auth/methods', body: {'phone': phone}) as Map<String, dynamic>;
    return data['has_password'] == true;
  }

  @override
  Future<SignIn> signInWithPassword({required String phone, required String password}) async {
    final data = await _api.post('/auth/login', body: {
      'phone': phone,
      'password': password,
      ...await _device(),
    }) as Map<String, dynamic>;
    return _signIn(data);
  }

  @override
  Future<WhatsappChallenge> startChallenge(String phone) async {
    final data = await _api.post('/auth/whatsapp/start', body: {'phone': phone}) as Map<String, dynamic>;
    return challengeFromJson(data);
  }

  @override
  Future<Duration?> checkChallenge(String challengeId) async {
    final data = await _api.post('/auth/whatsapp/check', body: {'challenge_id': challengeId})
        as Map<String, dynamic>;
    return otpValidityFromJson(data);
  }

  /// The answer to starting a WhatsApp challenge, for sign-in or a new number.
  static WhatsappChallenge challengeFromJson(Map<String, dynamic> data) => WhatsappChallenge(
        id: data['challenge_id'] as String,
        code: data['code'] as String,
        whatsappUrl: Uri.parse(data['whatsapp_url'] as String),
        validFor: Duration(seconds: data['expires_in_seconds'] as int),
        pollInterval: Duration(seconds: data['poll_interval_seconds'] as int? ?? 4),
      );

  /// Null while the customer's message has not arrived.
  static Duration? otpValidityFromJson(Map<String, dynamic> data) => data['otp_sent'] == true
      ? Duration(seconds: data['otp_expires_in_seconds'] as int? ?? 300)
      : null;

  @override
  Future<SignIn> verifyOtp({required String challengeId, required String otp}) async {
    final data = await _api.post('/auth/whatsapp/verify', body: {
      'challenge_id': challengeId,
      'otp': otp,
      ...await _device(),
    }) as Map<String, dynamic>;
    return _signIn(data);
  }

  @override
  Future<void> completeProfile({
    required String fullName,
    required String password,
    String? governorate,
    String? area,
  }) =>
      _api.post('/user/complete-profile', body: {
        'full_name': fullName,
        'password': password,
        'password_confirmation': password,
        if (governorate != null) 'governorate': governorate,
        if (area != null) 'area': area,
      });

  @override
  Future<void> setPassword(String password) => _api.put('/user/password', body: {
        'password': password,
        'password_confirmation': password,
      });

  static SignIn _signIn(Map<String, dynamic> data) {
    final user = data['user'] as Map<String, dynamic>?;
    final expiresAt = data['expires_at'] as String?;
    final boundMerchant = user?['bound_merchant'] as Map<String, dynamic>?;
    return SignIn(
      session: AuthSession(
        token: data['token'] as String,
        expiresAt: expiresAt == null ? null : DateTime.parse(expiresAt),
        fullName: user?['full_name'] as String?,
        phone: user?['phone_number'] as String?,
        boundMerchantId: boundMerchant?['id'] as int?,
        boundMerchantName: boundMerchant?['business_name'] as String?,
        governorate: user?['governorate'] as String?,
        area: user?['area'] as String?,
      ),
      hasPassword: user?['has_password'] == true,
    );
  }

  /// The fields every sign-in sends to identify this install.
  Future<Map<String, String>> _device() async {
    final platform = defaultTargetPlatform.name.toLowerCase();
    return {
      'device_name': await _deviceName(platform),
      // The API only knows these two push platforms.
      if (platform == 'android' || platform == 'ios') 'platform': platform,
    };
  }

  @override
  Future<void> signOut({String? pushToken}) =>
      _api.post('/auth/logout', body: {'device_token': ?pushToken});

  @override
  Future<String> enableQuickSignIn() async {
    final platform = defaultTargetPlatform.name.toLowerCase();
    final data = await _api.post('/auth/quick-login/enable', body: {
      'device_name': await _deviceName(platform),
    }) as Map<String, dynamic>;
    return data['secret'] as String;
  }

  @override
  Future<SignIn> quickSignIn(String secret) async {
    final data = await _api.post('/auth/quick-login', body: {
      'secret': secret,
      ...await _device(),
    }) as Map<String, dynamic>;
    return _signIn(data);
  }

  @override
  Future<void> disableQuickSignIn() async {
    final platform = defaultTargetPlatform.name.toLowerCase();
    await _api.post('/auth/quick-login/disable', body: {'device_name': await _deviceName(platform)});
  }

  /// The server keeps one live token per device name, so the name must be
  /// unique to this install or signing in here would sign out another phone.
  Future<String> _deviceName(String platform) async {
    var id = await _store.read(_deviceIdKey);
    if (id == null) {
      final random = Random.secure();
      id = List.generate(16, (_) => random.nextInt(16).toRadixString(16)).join();
      await _store.write(_deviceIdKey, id);
    }
    return '$platform-$id';
  }
}

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => ApiAuthRepository(ref.watch(apiClientProvider), ref.watch(keyValueStoreProvider)),
);
