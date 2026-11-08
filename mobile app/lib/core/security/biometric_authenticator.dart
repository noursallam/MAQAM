import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';

/// What the phone can confirm its owner with, so the app names it the way the
/// phone does.
enum BiometricKind {
  /// Nothing enrolled (or no sensor): the app offers its own PIN instead.
  none,

  /// Face ID on an iPhone, face unlock elsewhere.
  face,
  fingerprint,

  /// A sensor the system does not name (common on Android, which reports only
  /// "strong" or "weak").
  other,
}

/// Asks the device to confirm the person holding it (fingerprint or face).
abstract interface class BiometricAuthenticator {
  /// What this device has enrolled, or [BiometricKind.none].
  Future<BiometricKind> kind();

  /// Whether this device can confirm its owner with biometrics.
  Future<bool> isAvailable();

  /// Shows the system prompt. Returns false when cancelled or failed.
  Future<bool> authenticate(String reason);
}

class LocalAuthBiometricAuthenticator implements BiometricAuthenticator {
  LocalAuthBiometricAuthenticator([LocalAuthentication? auth]) : _auth = auth ?? LocalAuthentication();

  final LocalAuthentication _auth;

  @override
  Future<BiometricKind> kind() async {
    try {
      // Only what is enrolled counts: a phone with just a screen lock gets the app's PIN
      if (!await _auth.isDeviceSupported() || !await _auth.canCheckBiometrics) return BiometricKind.none;
      final types = await _auth.getAvailableBiometrics();
      if (types.isEmpty) return BiometricKind.none;
      if (types.contains(BiometricType.face) && defaultTargetPlatform == TargetPlatform.iOS) {
        return BiometricKind.face;
      }
      if (types.contains(BiometricType.fingerprint)) return BiometricKind.fingerprint;
      if (types.contains(BiometricType.face)) return BiometricKind.face;
      return BiometricKind.other;
    } on Exception {
      // No plugin on this platform (for example the web).
      return BiometricKind.none;
    }
  }

  @override
  Future<bool> isAvailable() async => await kind() != BiometricKind.none;

  @override
  Future<bool> authenticate(String reason) async {
    try {
      // Staying valid across backgrounding lets the prompt survive a phone call.
      return await _auth.authenticate(localizedReason: reason, persistAcrossBackgrounding: true);
    } on Exception {
      // Cancelled, locked out, or nothing enrolled: all mean "not confirmed".
      return false;
    }
  }
}

final biometricAuthenticatorProvider =
    Provider<BiometricAuthenticator>((ref) => LocalAuthBiometricAuthenticator());

/// What this phone has, read once; the screens name the lock after it.
final biometricKindProvider =
    FutureProvider<BiometricKind>((ref) => ref.watch(biometricAuthenticatorProvider).kind());
