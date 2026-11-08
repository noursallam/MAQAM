import '../../../core/session/auth_session.dart';
import 'whatsapp_challenge.dart';

/// A successful sign-in.
class SignIn {
  const SignIn({required this.session, required this.hasPassword});

  final AuthSession session;

  /// False for an account that has only ever signed in over WhatsApp; it is
  /// then asked for a name and a password of its own.
  final bool hasPassword;
}

abstract interface class AuthRepository {
  /// Whether [phone] (`01XXXXXXXXX`) signs in with a password. False also for
  /// a number that has no account yet, which registers over WhatsApp.
  Future<bool> hasPassword(String phone);

  Future<SignIn> signInWithPassword({required String phone, required String password});

  /// WhatsApp step 1: get the code the customer sends from [phone].
  Future<WhatsappChallenge> startChallenge(String phone);

  /// WhatsApp step 2: has the customer's message arrived? Returns how long the
  /// sign-in code is valid once it has been sent to them, or null while waiting.
  Future<Duration?> checkChallenge(String challengeId);

  /// WhatsApp step 3: exchange the sign-in code for a session.
  Future<SignIn> verifyOtp({required String challengeId, required String otp});

  /// First-time details for an account created over WhatsApp.
  Future<void> completeProfile({
    required String fullName,
    required String password,
    String? governorate,
    String? area,
  });

  /// Replaces a forgotten password. Only works in a session opened with a
  /// WhatsApp code.
  Future<void> setPassword(String password);

  /// Revokes the current token on the server, and stops pushes to this
  /// device when its [pushToken] is given.
  Future<void> signOut({String? pushToken});

  /// Lets this phone sign back in without the password. Returns the secret
  /// to keep in the encrypted store.
  Future<String> enableQuickSignIn();

  /// Signs in with this phone's secret, after the customer confirmed themselves on it.
  Future<SignIn> quickSignIn(String secret);

  /// Stops this phone signing back in on its own.
  Future<void> disableQuickSignIn();
}
