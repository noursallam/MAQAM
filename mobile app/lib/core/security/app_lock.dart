import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../session/session_provider.dart';
import '../storage/key_value_store.dart';
import 'biometric_authenticator.dart';

const _preferenceKey = 'biometric_lock';
const _pinKey = 'app_pin';
const _pinFailuresKey = 'app_pin_failures';

/// Digits in the app PIN.
const pinLength = 4;

/// Wrong PINs allowed before the PIN stops working until a fresh sign-in.
const maxPinAttempts = 5;

/// How the customer chose to lock the app: `enabled` is the device's
/// biometrics, `pin` is a PIN kept by the app.
enum BiometricPreference { undecided, enabled, pin, declined }

Future<BiometricPreference> readBiometricPreference(KeyValueStore store) async {
  try {
    final raw = await store.read(_preferenceKey);
    return BiometricPreference.values.asNameMap()[raw] ?? BiometricPreference.undecided;
  } on Exception {
    return BiometricPreference.undecided;
  }
}

/// Set by `main` to the preference read before the first frame.
final initialBiometricPreferenceProvider =
    Provider<BiometricPreference>((ref) => BiometricPreference.undecided);

class AppLockState {
  const AppLockState({required this.preference, required this.locked});

  final BiometricPreference preference;

  /// True while the app is waiting for the device owner to confirm themselves.
  final bool locked;

  bool get usesBiometrics => preference == BiometricPreference.enabled;

  bool get usesPin => preference == BiometricPreference.pin;

  bool get enabled => usesBiometrics || usesPin;
}

/// The outcome of typing a PIN on the lock screen.
class PinAttempt {
  const PinAttempt({required this.unlocked, required this.attemptsLeft});

  final bool unlocked;

  /// Zero means the PIN no longer works; the customer signs in again instead.
  final int attemptsLeft;
}

/// Locks the app behind the device's biometrics or a PIN of the app's own.
///
/// This guards the screen, not the token: the session stays in secure storage,
/// and the lock decides whether the app shows anything.
final appLockProvider = NotifierProvider<AppLockNotifier, AppLockState>(AppLockNotifier.new);

class AppLockNotifier extends Notifier<AppLockState> {
  @override
  AppLockState build() {
    // The choice outlives signing out: it is what lets the customer sign back in with
    // a touch. It is forgotten on purpose ([forget]) when someone else takes the phone.
    final preference = ref.read(initialBiometricPreferenceProvider);
    final enabled =
        preference == BiometricPreference.enabled || preference == BiometricPreference.pin;
    return AppLockState(
      preference: preference,
      locked: enabled && ref.read(sessionProvider) != null,
    );
  }

  BiometricAuthenticator get _authenticator => ref.read(biometricAuthenticatorProvider);

  KeyValueStore get _store => ref.read(keyValueStoreProvider);

  /// Whether to offer a lock after signing in: once, until the customer answers.
  bool get shouldOfferSetup => state.preference == BiometricPreference.undecided;

  /// Whether this device can confirm its owner with biometrics.
  Future<bool> isAvailable() => _authenticator.isAvailable();

  Future<bool> enable(String reason) async {
    if (!await _authenticator.authenticate(reason)) return false;
    await _store.delete(_pinKey);
    await _setPreference(BiometricPreference.enabled);
    return true;
  }

  /// Locks the app with [pin] from now on.
  Future<void> setPin(String pin) async {
    final random = Random.secure();
    final salt = List.generate(16, (_) => random.nextInt(256).toRadixString(16).padLeft(2, '0')).join();
    // Only a salted hash is kept, and that in the device's encrypted store.
    await _store.write(_pinKey, '$salt:${_hash(salt, pin)}');
    await _store.delete(_pinFailuresKey);
    await _setPreference(BiometricPreference.pin);
  }

  /// Used both for "not now" on the offer and for turning the lock off later.
  Future<void> decline() async {
    await _store.delete(_pinKey);
    await _store.delete(_pinFailuresKey);
    await _setPreference(BiometricPreference.declined);
  }

  Future<bool> unlock(String reason) async {
    if (!await _authenticator.authenticate(reason)) return false;
    state = AppLockState(preference: state.preference, locked: false);
    return true;
  }

  /// How many more wrong PINs are allowed.
  Future<int> pinAttemptsLeft() async {
    final failures = int.tryParse(await _store.read(_pinFailuresKey) ?? '') ?? 0;
    return max(0, maxPinAttempts - failures);
  }

  Future<PinAttempt> unlockWithPin(String pin) async {
    final left = await pinAttemptsLeft();
    if (left == 0) return const PinAttempt(unlocked: false, attemptsLeft: 0);

    final parts = (await _store.read(_pinKey) ?? '').split(':');
    if (parts.length == 2 && _hash(parts[0], pin) == parts[1]) {
      await _store.delete(_pinFailuresKey);
      state = AppLockState(preference: state.preference, locked: false);
      return const PinAttempt(unlocked: true, attemptsLeft: maxPinAttempts);
    }

    // Counted on disk, so closing the app does not hand out fresh guesses.
    await _store.write(_pinFailuresKey, '${maxPinAttempts - left + 1}');
    return PinAttempt(unlocked: false, attemptsLeft: left - 1);
  }

  /// A fresh sign-in proves ownership more strongly than the lock does.
  Future<void> unlockAfterSignIn() async {
    state = AppLockState(preference: state.preference, locked: false);
    await _store.delete(_pinFailuresKey);
  }

  void lock() {
    if (state.enabled && ref.read(sessionProvider) != null) {
      state = AppLockState(preference: state.preference, locked: true);
    }
  }

  static String _hash(String salt, String pin) => sha256.convert(utf8.encode('$salt:$pin')).toString();

  Future<void> _setPreference(BiometricPreference preference) async {
    await _store.write(_preferenceKey, preference.name);
    state = AppLockState(preference: preference, locked: false);
  }

  /// Back to "never asked", for a different customer on this phone.
  Future<void> forget() async {
    state = const AppLockState(preference: BiometricPreference.undecided, locked: false);
    await _store.delete(_preferenceKey);
    await _store.delete(_pinKey);
    await _store.delete(_pinFailuresKey);
  }
}
