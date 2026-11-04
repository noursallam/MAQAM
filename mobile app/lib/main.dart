import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app/app.dart';
import 'core/security/app_lock.dart';
import 'core/session/quick_sign_in.dart';
import 'core/session/session_provider.dart';
import 'core/storage/key_value_store.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  const store = SecureKeyValueStore();
  final session = await readStoredSession(store);
  final biometricPreference = await readBiometricPreference(store);
  final quickSignIn = await readQuickSignIn(store);

  runApp(
    ProviderScope(
      // A failed load shows its error and a retry button instead of retrying silently.
      retry: (retryCount, error) => null,
      overrides: [
        keyValueStoreProvider.overrideWithValue(store),
        initialSessionProvider.overrideWithValue(session),
        initialBiometricPreferenceProvider.overrideWithValue(biometricPreference),
        initialQuickSignInProvider.overrideWithValue(quickSignIn),
      ],
      child: const MaqamApp(),
    ),
  );
}
